# Financial Category Reporting System

## نظام التقارير المالية للتصنيفات

نظام شامل ومتكامل لإنشاء تقارير مفصلة وإحصائيات متقدمة للتصنيفات المالية.

---

## 📋 Quick Start

### Base URL

```
/api/financial/categories/
```

### Authentication

جميع الـ endpoints تحتاج `Bearer Token` في الـ header:

```
Authorization: Bearer YOUR_TOKEN
```

---

## 🚀 Available Endpoints

| Endpoint        | Method | Description       |
| --------------- | ------ | ----------------- |
| `/report`       | GET    | تقرير شامل ومفصل  |
| `/statistics`   | GET    | إحصائيات متقدمة   |
| `/summary`      | GET    | ملخص سريع         |
| `/trends`       | GET    | تحليل الاتجاهات   |
| `/comparison`   | GET    | مقارنة فترتين     |
| `/{id}/details` | GET    | تفاصيل تصنيف محدد |

---

## 🎯 Common Filters

يمكن استخدام هذه الفلاتر مع جميع الـ endpoints:

| Parameter        | Type    | Description        | Example                      |
| ---------------- | ------- | ------------------ | ---------------------------- |
| `start_date`     | date    | تاريخ البداية      | `2025-01-01`                 |
| `end_date`       | date    | تاريخ النهاية      | `2025-12-31`                 |
| `type`           | string  | نوع التصنيف        | `income` or `expense`        |
| `category_ids[]` | array   | معرفات التصنيفات   | `[1, 2, 3]`                  |
| `branch_id`      | integer | معرف الفرع         | `1`                          |
| `status`         | string  | حالة المعاملة      | `paid`, `pending`, `overdue` |
| `min_amount`     | decimal | الحد الأدنى للمبلغ | `100.00`                     |
| `max_amount`     | decimal | الحد الأقصى للمبلغ | `5000.00`                    |

---

## 💡 Usage Examples

### 1. الحصول على ملخص سريع

```bash
GET /api/financial/categories/summary?start_date=2025-01-01&end_date=2025-12-31
```

### 2. تقرير الدخل فقط

```bash
GET /api/financial/categories/report?type=income&start_date=2025-01-01
```

### 3. إحصائيات فرع معين

```bash
GET /api/financial/categories/statistics?branch_id=1&start_date=2025-01-01
```

### 4. مقارنة نصفي السنة

```bash
GET /api/financial/categories/comparison
Content-Type: application/json

{
  "period_one": {
    "start_date": "2025-01-01",
    "end_date": "2025-06-30"
  },
  "period_two": {
    "start_date": "2025-07-01",
    "end_date": "2025-12-31"
  }
}
```

---

## 📊 Response Structure

### Summary Response

```json
{
    "success": true,
    "data": {
        "total_income": 150000.0,
        "total_expense": 80000.0,
        "net_balance": 70000.0,
        "total_transactions": 250
    }
}
```

### Full Report Response

```json
{
  "report_info": {
    "generated_at": "2025-11-21T09:00:00+03:00",
    "date_range": {...},
    "filters_applied": {...}
  },
  "statistics": {
    "totals": {...},
    "averages": {...},
    "trends": {...}
  },
  "category_summaries": [...]
}
```

---

## 🏗️ Architecture

```
Services/Financial/
├── Filters/
│   └── FinancialCategoryReportFilter.php
├── Aggregators/
│   └── FinancialTransactionAggregatorService.php
├── Statistics/
│   └── FinancialCategoryStatisticsService.php
└── Reports/
    └── FinancialCategoryReportService.php

DTOs/Financial/
├── CategoryTransactionSummaryDTO.php
├── FinancialCategoryStatisticsDTO.php
└── FinancialCategoryReportDTO.php

Http/
├── Controllers/Api/Financial/
│   └── FinancialCategoryReportController.php
└── Resources/Financial/
    ├── FinancialCategoryReportResource.php
    ├── FinancialCategoryStatisticsResource.php
    └── CategoryTransactionSummaryResource.php
```

---

## ✨ Features

-   ✅ فلترة متقدمة ومرنة
-   ✅ إحصائيات شاملة ومفصلة
-   ✅ تحليل الاتجاهات الشهرية
-   ✅ مقارنة الفترات الزمنية
-   ✅ توزيعات حسب الفروع والحالات
-   ✅ معدلات النمو والتغيير
-   ✅ أعلى التصنيفات أداءً
-   ✅ استعلامات محسنة للأداء

---

## 📝 Notes

-   جميع المبالغ بصيغة `decimal(2)` (رقمين عشريين)
-   التواريخ بصيغة `Y-m-d` (مثال: `2025-01-01`)
-   الاستجابات بصيغة JSON
-   يتطلب Authentication لجميع الـ endpoints

---

## 🔗 Related Files

-   Models: `FinancialCategory`, `FinancialTransaction`
-   Migrations: `create_financial_categories_table`, `create_financial_transactions_table`
-   Resources: `FinancialCategoryResource`, `FinancialTransactionResource`

---

تم التطوير بواسطة: Antigravity AI 🚀
