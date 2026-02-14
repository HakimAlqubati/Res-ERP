<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Logic Flow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mermaid/10.9.1/mermaid.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .mermaid {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(200 200 200 / 0.5);
            padding: 2rem;
        }
    </style>
</head>

<body class="p-8 text-slate-800">

    <div class="max-w-7xl mx-auto text-center">
        <h1 class="text-3xl font-bold text-slate-900">Payroll Calculation Pipeline</h1>
        <p class="text-slate-500 mt-2">Main Classes Involved in Salary Calculation</p>

        <div class="card mt-8 overflow-hidden">
            <div class="mermaid">
                %%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#eff6ff', 'edgeLabelBackground':'#ffffff', 'tertiaryColor': '#ffffff'}}}%%
                graph TD
                %% Orchestrator
                SCS[SalaryCalculatorService]

                %% Components
                RC[RateCalculator]
                ADC[AttendanceDeductionCalculator]
                OTC[OvertimeCalculator]
                AC[AllowanceCalculator]
                MIC[MonthlyIncentiveCalculator]
                PC[PenaltyCalculator]
                AIC[AdvanceInstallmentCalculator]
                MRC[MealRequestCalculator]
                GDC[GeneralDeductionCalculator]

                %% Finalizer
                TB[TransactionBuilder]

                %% Flow
                SCS -->|1. Get Rates| RC
                SCS -->|2. Get Absence/Late| ADC
                SCS -->|3. Get Overtime| OTC
                SCS -->|4. Get Allowances| AC
                SCS -->|5. Get Incentives| MIC
                SCS -->|6. Get Penalties| PC
                SCS -->|7. Get Advances| AIC
                SCS -->|8. Get Meals| MRC
                SCS -->|9. Get Taxes/GOSI| GDC
                SCS -->|10. Build Final Records| TB

                style SCS fill:#10b981,stroke:#059669,color:white,stroke-width:2px
                style TB fill:#3b82f6,stroke:#2563eb,color:white,stroke-width:2px

                style ADC fill:#fee2e2,stroke:#ef4444
                style GDC fill:#fee2e2,stroke:#ef4444

                style AC fill:#dcfce7,stroke:#22c55e
                style OTC fill:#dcfce7,stroke:#22c55e
                style MIC fill:#dcfce7,stroke:#22c55e
            </div>
        </div>

        <div class="mt-6 text-sm text-slate-500">
            <span class="inline-block w-3 h-3 bg-red-100 border border-red-500 rounded-full mr-1"></span> Deductions
            <span class="inline-block w-3 h-3 bg-green-100 border border-green-500 rounded-full mr-1 ml-4"></span> Additions
        </div>
    </div>

    <script>
        mermaid.initialize({
            startOnLoad: true
        });
    </script>
</body>

</html>