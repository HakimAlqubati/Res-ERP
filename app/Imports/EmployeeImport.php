<?php

namespace App\Imports;

use Exception;
use App\Models\Employee;
use App\Models\AppLog;
use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class EmployeeImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsEmptyRows
{
    use SkipsErrors;

    private $successfulImportsCount = 0;
    private $existingCount = 0;
    private $processedRowsCount = 0;
    private $currentRowNumber = 1;
    private $importErrors = [];

    public function model(array $row)
    {
        $this->currentRowNumber++;
        $rowNumber = $this->currentRowNumber;

        // Parse the join_date field
        try {
            $joinDate = isset($row['join_date']) && !empty($row['join_date'])
                ? Carbon::parse($row['join_date'])->format('Y-m-d')
                : null;
        } catch (Exception $e) {
            $errorMessage = "Row {$rowNumber}: Invalid join_date format '{$row['join_date']}' - {$e->getMessage()}";
            $this->logError('DATE_PARSE_ERROR', $errorMessage, $row, $e);
            $joinDate = null;
        }

        try {
            // التحقق من وجود الاسم
            if (!isset($row['name']) || empty($row['name'])) {
                $errorMessage = "Row {$rowNumber}: Employee name is missing or empty";
                $this->logError('MISSING_NAME', $errorMessage, $row);
                return null;
            }

            // التحقق من تكرار الاسم - إذا موجود نعتبره existing وليس خطأ
            if (Employee::where('name', $row['name'])->exists()) {
                $this->existingCount++;
                $this->processedRowsCount++;
                return null;
            }

            // التحقق من وجود branch_id
            if (!isset($row['branch_id']) || empty($row['branch_id'])) {
                $errorMessage = "Row {$rowNumber}: Branch ID is missing for employee '{$row['name']}'";
                $this->logError('MISSING_BRANCH_ID', $errorMessage, $row);
            }

            // التحقق من صحة البريد الإلكتروني
            if (isset($row['email']) && !empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $errorMessage = "Row {$rowNumber}: Invalid email format '{$row['email']}' for employee '{$row['name']}'";
                $this->logError('INVALID_EMAIL_FORMAT', $errorMessage, $row);
            }

            // التحقق من صحة الراتب
            if (isset($row['salary']) && !is_numeric($row['salary'])) {
                $errorMessage = "Row {$rowNumber}: Invalid salary value '{$row['salary']}' for employee '{$row['name']}'";
                $this->logError('INVALID_SALARY', $errorMessage, $row);
            }

            $this->successfulImportsCount++;
            $this->processedRowsCount++;

            // تعطيل الـ Observer أثناء الاستيراد لتجنب إنشاء مستخدم تلقائياً
            // استخدام updateOrCreate للتعامل مع الموظفين المكررين (تحديث إذا موجود، إنشاء إذا جديد)
            return Employee::withoutEvents(function () use ($row, $joinDate) {
                return Employee::updateOrCreate(
                    // البحث بناءً على employee_no (المفتاح الفريد)
                    ['employee_no' => $row['employee_no'] ?? null],
                    // البيانات للتحديث أو الإنشاء
                    [
                        'name' => $row['name'],
                        'phone_number' => $row['phone_number'] ?? null,
                        'job_title' => $row['job_title'] ?? null,
                        'email' => $row['email'] ?? null,
                        'salary' => $row['salary'] ?? 0,
                        'nationality' => $row['nationality'] ?? null,
                        'has_employee_pass' => $this->parseHasEmployeePass($row['has_employee_pass'] ?? null),
                        'gender' => $this->parseGender($row['gender'] ?? null),
                        'branch_id' => $row['branch_id'] ?? null,
                        'join_date' => $joinDate,
                    ]
                );
            });
        } catch (Exception $e) {
            $employeeName = $row['name'] ?? 'Unknown';
            $errorMessage = "Row {$rowNumber}: Exception while importing employee '{$employeeName}' - {$e->getMessage()}";
            $this->logError('EXCEPTION', $errorMessage, $row, $e);
            return null;
        }
    }

    /**
     * معالجة أخطاء قاعدة البيانات والاستثناءات الأخرى
     * يتم استدعاؤها تلقائياً عند حدوث خطأ في الحفظ
     */
    public function onError(\Throwable $e)
    {
        $errorMessage = "Database/System Error during import: {$e->getMessage()}";
        $this->logError('DATABASE_ERROR', $errorMessage, [], $e);
    }

    /**
     * تسجيل الخطأ في AppLog وفي المصفوفة المحلية
     */
    private function logError(string $errorType, string $message, array $rowData = [], ?\Throwable $exception = null): void
    {
        $this->importErrors[] = [
            'type' => $errorType,
            'message' => $message,
        ];

        // تسجيل في AppLog للأخطاء الفادحة فقط
        $criticalErrors = ['EXCEPTION', 'DATABASE_ERROR'];
        if (in_array($errorType, $criticalErrors)) {
            AppLog::write(
                message: $message,
                level: AppLog::LEVEL_ERROR,
                context: 'EmployeeImport',
                extra: [
                    'error_type' => $errorType,
                    'row_data' => $rowData,
                    'exception_class' => $exception ? get_class($exception) : null,
                ]
            );
        }
    }



    /**
     * تحويل قيمة has_employee_pass من النص إلى رقم صحيح
     * القيم مثل "Yes", "ESD", "1", "true" تعني أن الموظف لديه تصريح = 1
     * القيم مثل "No", "NR", "0", "false", null, empty تعني لا يوجد تصريح = 0
     */
    private function parseHasEmployeePass($value): int
    {
        if (empty($value) || $value === null) {
            return 0;
        }

        // إذا كانت القيمة رقمية
        if (is_numeric($value)) {
            return (int) $value > 0 ? 1 : 0;
        }

        // تحويل القيمة النصية إلى أحرف صغيرة للمقارنة
        $value = strtolower(trim((string) $value));

        // القيم التي تعني "نعم" أو لديه تصريح
        $positiveValues = ['yes', 'esd', 'true', '1', 'y', 'نعم'];

        // القيم التي تعني "لا" أو ليس لديه تصريح
        $negativeValues = ['no', 'nr', 'false', '0', 'n', 'لا'];

        if (in_array($value, $positiveValues)) {
            return 1;
        }

        if (in_array($value, $negativeValues)) {
            return 0;
        }

        // للقيم غير المعروفة، نعتبرها 0 (لا يوجد تصريح)
        return 0;
    }

    /**
     * تحويل قيمة gender من النص إلى رقم صحيح
     * Male = 1, Female = 2, غير محدد = null
     */
    private function parseGender($value): ?int
    {
        if (empty($value) || $value === null) {
            return null;
        }

        // إذا كانت القيمة رقمية
        if (is_numeric($value)) {
            return (int) $value;
        }

        // تحويل القيمة النصية إلى أحرف صغيرة للمقارنة
        $value = strtolower(trim((string) $value));

        // القيم التي تعني ذكر
        $maleValues = ['male', 'm', 'ذكر', '1'];

        // القيم التي تعني أنثى
        $femaleValues = ['female', 'f', 'أنثى', '2'];

        if (in_array($value, $maleValues)) {
            return 1;
        }

        if (in_array($value, $femaleValues)) {
            return 2;
        }

        // للقيم غير المعروفة
        return null;
    }

    /**
     * الحصول على جميع الأخطاء المسجلة
     */
    public function getImportErrors(): array
    {
        return $this->importErrors;
    }

    /**
     * الحصول على عدد الأخطاء
     */
    public function getErrorCount(): int
    {
        return count($this->importErrors);
    }

    /**
     * التحقق من وجود أخطاء
     */
    public function hasErrors(): bool
    {
        return !empty($this->importErrors);
    }


    public function headings(): array
    {
        return [
            'name',
            'phone_number',
            'job_title',
            'email',
            'salary',
            'branch_id',
            'nationality',
            'gender',
            'has_employee_pass',
            'join_date',
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function getSuccessfulImportsCount(): int
    {
        return $this->successfulImportsCount;
    }

    public function getExistingCount(): int
    {
        return $this->existingCount;
    }

    public function getProcessedRowsCount(): int
    {
        return $this->processedRowsCount;
    }
}
