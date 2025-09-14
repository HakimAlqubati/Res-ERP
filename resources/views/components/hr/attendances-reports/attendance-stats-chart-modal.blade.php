{{-- مودال الشارت الاحترافي جدا --}}
<style>
#chart-modal-erp {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.32);
    transition: background 0.38s cubic-bezier(.65,0,.20,1);
}
#chart-modal-erp[showing] { background: rgba(19, 92, 168, 0.15); }
#chart-modal-erp .erp-modal-content {
    max-width: 570px;
    margin: 62px auto;
    background: #fff;
    border-radius: 30px;
    padding: 40px 36px 32px 36px;
    position: relative;
    box-shadow: 0 22px 66px 0 rgba(40, 140, 220, .20), 0 2px 8px 0 #3ac1e310;
    animation: pop-in .5s cubic-bezier(.62, .28, .23, .99);
    min-height: 520px;
}
@keyframes pop-in {
    0% { transform: scale(0.96); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.erp-close-btn {
    position: absolute; top: 14px; right: 16px; font-size: 28px;
    background: none; border: none; color: #888; cursor: pointer; transition: color .15s;
}
.erp-close-btn:hover { color: #e74c3c; }
.erp-modal-title {
    font-size: 2.25rem; font-weight: 900; letter-spacing: .5px; color: #1670b6;
    margin-bottom: 8px; text-align: center;
}
.erp-employee-info {
    display: flex; align-items: center; justify-content: center;
    font-size: 1.20rem; font-weight: 800; color: #333; margin-bottom: 18px; gap: 10px;
}
.erp-employee-info i { font-size: 1.8rem; color: #1670b6; margin-left: 8px; }
.erp-chart-legend { display: flex; justify-content: center; flex-wrap: wrap; gap: 18px; margin-top: 29px; font-size: 1.15rem; }
.erp-legend-dot { display: inline-block; width: 17px; height: 17px; border-radius: 50%; margin-right: 8px; vertical-align: middle; box-shadow:0 1.5px 8px #d8f2ff40; }
.erp-print-btn {
    margin: 20px auto 0 10px; display: inline-block;
    padding: 10px 33px; border-radius: 12px; border: none; background: linear-gradient(90deg, #199fe6 60%, #36e0a4 100%);
    color: #fff; font-size: 1.10rem; font-weight: 700; box-shadow: 0 2px 10px #32b1d41b;
    width: 100%;
    cursor: pointer; transition: background .18s;
}
.erp-print-btn:hover { background: linear-gradient(90deg, #0ecbfb 50%, #40e3d4 100%);}
.erp-btn-row { display:flex; justify-content:center; gap:10px; margin-top:20px; }
#attendanceChartERP {
    margin: 0 auto 12px auto; max-width: 360px; background: radial-gradient(circle at 65% 18%, #f2fcff 78%, #c6ebfa 100%);
    border-radius: 50%; box-shadow: 0 0 0 13px #eaf7ff, 0 6px 44px 0 #a1e8ff45;
    position: relative; z-index: 2;
}
#erp-chart-center-stat {
    position:absolute; top:51%; left:50%; transform:translate(-50%,-50%);
    font-size:2.1rem;font-weight:900; color:#1670b6; text-shadow:0 2px 11px #e2effd; pointer-events:none;
    text-align:center; letter-spacing:.1px; line-height:1.2;
}
#no-data-message-erp { text-align: center; color: #aaa; font-size: 20px; margin-top: 50px;}
@media print {
    body *:not(#chart-modal-erp):not(#chart-modal-erp *) { visibility: hidden !important;}
    #chart-modal-erp, #chart-modal-erp * { visibility: visible !important;}
    #chart-modal-erp { background: #fff !important;}
    #chart-modal-erp .erp-close-btn, .erp-btn-row { display: none !important;}
    #chart-modal-erp .erp-modal-content { margin: 12px auto !important; box-shadow: none !important;}
}
@media (max-width:680px) {
    #chart-modal-erp .erp-modal-content { padding: 15px 6px 10px 6px;}
    .erp-modal-title { font-size: 1.13rem;}
}
</style>

<div id="chart-modal-erp">
    <div class="erp-modal-content">
        <button onclick="hideChartModalERP()" class="erp-close-btn">&times;</button>
        <div class="erp-modal-title">📊 {{ __('Attendance Statistics') }}</div>
        <div class="erp-employee-info">
            <i class="fa fa-user-circle"></i>
            {{ $employee_name ?? '-' }}
        </div>
        <div style="position:relative; display:flex; justify-content:center;">
            <canvas id="attendanceChartERP" width="350" height="350"></canvas>
            <div id="erp-chart-center-stat"></div>
        </div>
        <div id="no-data-message-erp" style="display:none;">{{ __('No Data Available') }}</div>
        <div class="erp-chart-legend" id="erp-chart-legend"></div>
        <div class="erp-btn-row">
            <button class="erp-print-btn" onclick="printERPChart()">🖨️ {{ __('Print This Statistics') }}</button> 
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
window.chartModalDataERP = @json($chartData);
window.chartEmployeeName = @json($employee_name ?? '-');

function showChartModal() {
    let modal = document.getElementById('chart-modal-erp');
    modal.style.display = 'block';
    setTimeout(()=>{ modal.setAttribute('showing',''); },40);
    setTimeout(renderChartERP, 200);
}
function hideChartModalERP() {
    let modal = document.getElementById('chart-modal-erp');
    modal.removeAttribute('showing');
    setTimeout(()=>{ modal.style.display = 'none'; },260);
}
function printERPChart() { window.print(); }

let attendanceChartERP;

function renderChartERP() {
    let data = window.chartModalDataERP;
    let canvas = document.getElementById('attendanceChartERP');
    let centerStatDiv = document.getElementById('erp-chart-center-stat');
    let noDataDiv = document.getElementById('no-data-message-erp');
    let legendDiv = document.getElementById('erp-chart-legend');
    if (!canvas) return;

    // تحقق من البيانات
    if (!data || !data.values || data.values.reduce((a, b) => a + b, 0) === 0) {
        canvas.style.display = 'none'; centerStatDiv.innerHTML=''; noDataDiv.style.display = '';
        legendDiv.innerHTML = '';
        return;
    }
    canvas.style.display = ''; noDataDiv.style.display = 'none';

    let ctx = canvas.getContext('2d');
    if (attendanceChartERP) attendanceChartERP.destroy();

    attendanceChartERP = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: data.colors,
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 12
            }]
        },
        options: {
            responsive: false,
            cutout: "62%",
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            let val = ctx.parsed;
                            let total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            let perc = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                            return `${ctx.label}: ${val} (${perc}%)`;
                        }
                    }
                },
                datalabels: {
                    display: true,
                    color: '#222',
                    font: { weight: 'bold', size: 19 },
                    formatter: function(value, ctx) {
                        let total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        let perc = total > 0 ? ((value / total) * 100).toFixed(0) : 0;
                        return value > 0 ? perc + '%' : '';
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 1300,
                easing: 'easeOutBounce'
            }
        },
        plugins: [window.ChartDataLabels || {}]
    });

    // إحصائية المنتصف: الأكثر عدداً
    let maxIdx = data.values.indexOf(Math.max(...data.values));
    let maxLabel = data.labels[maxIdx] || '';
    let maxVal = data.values[maxIdx] || 0;
    let total = data.values.reduce((a,b)=>a+b,0);
    let perc = total > 0 ? ((maxVal/total)*100).toFixed(1) : 0;
    centerStatDiv.innerHTML = `<span style="font-size:1.04rem;color:#888;font-weight:600;line-height:1;">${maxLabel}</span><br>
        <span style="font-size:2.13rem;color:${data.colors[maxIdx]};font-weight:900;line-height:1.12;">${perc}%</span>`;

    // Legend يدوي مع أيقونات وحالة بالألوان
    let icons = [
        '<i class="fa fa-check-circle" style="color:#28a745"></i>',      // Present
        '<i class="fa fa-times-circle" style="color:#dc3545"></i>',     // Absent
        '<i class="fa fa-exclamation-triangle" style="color:#17a2b8"></i>', // Partial
        '<i class="fa fa-bed" style="color:#ffc107"></i>',              // Leave
        '<i class="fa fa-minus-circle" style="color:#6c757d"></i>',     // No periods
    ];
    legendDiv.innerHTML = data.labels.map(function(lbl, idx) {
        return `<span><span class="erp-legend-dot" style="background:${data.colors[idx]}"></span>${icons[idx]??''} ${lbl} <b>(${data.values[idx]})</b></span>`;
    }).join('');
}

// تحميل بلجن datalabels الخاص بـ Chart.js
window.ChartDataLabels = undefined;
(function() {
    var script = document.createElement('script');
    script.src = "https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels";
    script.onload = function() { window.ChartDataLabels = ChartDataLabels; }
    document.head.appendChild(script);
})();
</script>
