<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تجربة احتساب الراتب الشهري</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f5f8fc; margin:0; }
        .box { max-width:430px; margin:50px auto; background:#fff; border-radius:16px; box-shadow:0 3px 14px #0043cc0a; padding:30px 26px; }
        h2 { text-align:center; color:#3d76bb; }
        table { width:100%; margin-top:14px; border-collapse:collapse;}
        td { padding:7px 5px; }
        th { text-align:right; color:#888; font-size:1em;}
        .pos { color:#108455; font-weight:bold;}
        .neg { color:#e04b6e; font-weight:bold;}
        .result { background:#ecf8ef; font-size:1.25em; font-weight:bold; text-align:center; border-radius:10px; margin-top:20px; padding:12px 0;}
        .small-table {background: #f4f8fd; border-radius: 10px; margin-bottom: 10px;}
        .small-table td, .small-table th {padding: 4px 8px;}
    </style>
</head>
<body>
    <div class="box">
        <h2>تجربة احتساب الراتب الشهري</h2>
        <table>
            <tr>
                <th>الموظف</th>
                <td>{{ $employee->name }}</td>
            </tr>
            <tr>
                <th>الشهر/السنة</th>
                <td>{{ $month }} / {{ $year }}</td>
            </tr>
            <tr>
                <th>الراتب الأساسي</th>
                <td class="pos">{{ number_format($basicSalary,2) }}</td>
            </tr>
        </table>
        <table class="small-table">
            <tr>
                <th>الأجرة اليومية</th>
                <td>{{ number_format($dayWage,2) }}</td>
                <th>الأجرة بالساعة</th>
                <td>{{ number_format($hourWage,2) }}</td>
                <th>الأجرة بالدقيقة</th>
                <td>{{ number_format($minuteWage,4) }}</td>
            </tr>
        </table>
        <table>
            <tr>
                <th>حوافز الإضافي</th>
                <td class="pos">+{{ number_format($overtimeBonus,2) }} <span style="font-size:0.88em;color:#aaa">(عدد الساعات: {{ $overtimeHours }})</span></td>
            </tr>
            <tr>
                <th>خصم الغياب</th>
                <td class="neg">-{{ number_format($absenceDeduction,2) }} <span style="font-size:0.88em;color:#aaa">(عدد الأيام: {{ $absentDays }})</span></td>
            </tr>
            <tr>
                <th>خصم التأخير</th>
                <td class="neg">-{{ number_format($lateDeduction,2) }} <span style="font-size:0.88em;color:#aaa">(عدد الدقائق: {{ $lateMinutes }})</span></td>
            </tr>
            <tr>
                <th>سعر ساعة الإضافي</th>
                <td>{{ number_format($overtimeHourRate,2) }}</td>
            </tr>
        </table>
        <div class="result">
            الصافي النهائي: {{ number_format($net,2) }}
        </div>
    </div>
</body>
</html>
