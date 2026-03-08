<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$uid    = $isLoggedIn ? (int)($_SESSION['user_id']) : null;
$uemail = $isLoggedIn ? ($_SESSION['email'] ?? '')   : '';
$uname  = $isLoggedIn ? ($_SESSION['username'] ?? 'User') : 'Guest';
?>

<script>
  <?php if ($isLoggedIn): ?>
    localStorage.setItem("mmm_session", "user");
    localStorage.setItem("mmm_user", <?= json_encode($uname) ?>);
    localStorage.setItem("mmm_user_id", <?= json_encode($uid) ?>);
    localStorage.setItem("mmm_user_email", <?= json_encode($uemail) ?>);
  <?php else: ?>
    localStorage.setItem("mmm_session", "guest");
    localStorage.removeItem("mmm_user");
    localStorage.removeItem("mmm_user_id");
    localStorage.removeItem("mmm_user_email");
  <?php endif; ?>
</script>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Retirement Planner</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Prompt', sans-serif;
      background: linear-gradient(to bottom right, #e0f7fa, #f1f8e9);
      margin: 0;
      padding: 20px;
    }
    .container {
      max-width: 900px;
      margin: auto;
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 { text-align: center; color: #00796b; }
    .topbar { display:flex; justify-content:flex-end; gap:8px; }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 16px;
      margin-top: 20px;
    }
    label { font-weight: bold; }
    input {
      width: 100%; padding: 10px; font-size: 16px; margin-top: 4px;
      border-radius: 6px; border: 1px solid #ccc;
    }
    .btn {
      margin-top: 16px; padding: 12px 20px; border: none; border-radius: 8px;
      font-size: 16px; cursor: pointer; width: 100%;
    }
    .btn-primary { background-color: #00796b; color: white; }
    .btn-secondary { background-color: #10b981; color: #fff; }
    .btn-link {
      padding: 8px 14px; border-radius: 20px; border: 1px solid #cbd5e1;
      background: #f8fafc; color:#0f766e; cursor:pointer; width:auto; margin-top:0;
    }
    .btn-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 8px; }
    .lang-select {
      padding: 6px 12px; border-radius: 20px; border: 1px solid #ccc; font-size: 0.9rem; color: #00796b;
    }
    canvas { margin-top: 28px; max-height: 400px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="topbar">
      <button id="btn-back" class="btn-link">← Dashboard</button>
      <select id="lang-select" class="lang-select" onchange="changeLanguage()">
        <option value="en">English</option>
        <option value="th">ไทย</option>
      </select>
    </div>

    <h2 id="page-title">Retirement Planner</h2>

    <div class="form-grid">
      <div>
        <label id="label-currentAge">Current Age</label>
        <input type="text" id="currentAge" value="30" oninput="formatNumber(this)">
      </div>
      <div>
        <label id="label-retireAge">Retirement Age</label>
        <input type="text" id="retireAge" value="60" oninput="formatNumber(this)">
      </div>
      <div>
        <label id="label-currentSavings">Current Savings (Baht)</label>
        <input type="text" id="currentSavings" value="500,000" oninput="formatNumber(this)">
      </div>
      <div>
        <label id="label-monthlyIncome">Monthly Income (Baht)</label>
        <input type="text" id="monthlyIncome" value="30,000" oninput="formatNumber(this)">
      </div>
      <div>
        <label id="label-monthlyExpense">Monthly Expenses (Baht)</label>
        <input type="text" id="monthlyExpense" value="20,000" oninput="formatNumber(this)">
      </div>
      <div>
        <label id="label-annualReturn">Annual Return (%)</label>
        <input type="text" id="annualReturn" value="5" oninput="formatNumber(this)">
      </div>
    </div>

    <div class="btn-row">
      <button class="btn btn-primary" onclick="calculate()" id="btn-calculate">Calculate</button>
      <button class="btn btn-secondary" id="btn-save-ret">Save Calculation</button>
    </div>

    <canvas id="retirementChart"></canvas>
    <div style="margin:24px auto 0 auto; max-width:400px; text-align:center;">
      <label id="label-ageLookup" for="ageLookup" style="font-weight:bold;">ดูเงินออมสุทธิหลังหักค่าใช้จ่ายที่อายุ (ปี):</label>
      <input type="number" id="ageLookup" min="0" max="100" style="width:100px; margin-left:8px;">
      <span id="ageLookupResult" style="margin-left:12px; font-weight:bold; color:#00796b;"></span>
    </div>
  </div>

  <!-- ===== MMMHistory (Account-scoped, Guest-aware v3) ===== -->
  <script>
    (function(){
      if (window.MMMHistory && window.MMMHistory.__v === '3') return;

      const KEY_BASE = "mmm_history_v1";
      const isUser = ()=> (localStorage.getItem('mmm_session') === 'user');

      function accountId(){
        return (
          localStorage.getItem('mmm_user_id') ||
          localStorage.getItem('mmm_user_email') ||
          localStorage.getItem('mmm_user') ||
          'guest'
        );
      }
      function key(){ return `${KEY_BASE}::${accountId()}`; }

      function uid(){ return Date.now().toString(36) + Math.random().toString(36).slice(2,8); }
      function getAll(){ try { return JSON.parse(localStorage.getItem(key())) || []; } catch(e){ return []; } }
      function setAll(list){ localStorage.setItem(key(), JSON.stringify(list)); }

      function save(entry){
        if (!isUser()) {
          alert((localStorage.getItem('language')==='th')
            ? 'โหมดผู้เยี่ยมชม: โปรดเข้าสู่ระบบเพื่อบันทึกประวัติ'
            : 'Guest mode: Please log in to save your history.'
          );
          return null;
        }
        const item = { id: uid(), createdAt: new Date().toISOString(), ...entry };
        const list = getAll(); list.unshift(item); setAll(list); return item.id;
      }
      function remove(id){
        if (!isUser()) {
          alert((localStorage.getItem('language')==='th')
            ? 'โหมดผู้เยี่ยมชม: ไม่สามารถลบรายการได้'
            : 'Guest mode: Delete is disabled.'
          );
          return;
        }
        const list = getAll().filter(x => x.id !== id);
        setAll(list);
      }
      function clear(){
        if (!isUser()) {
          alert((localStorage.getItem('language')==='th')
            ? 'โหมดผู้เยี่ยมชม: ไม่สามารถลบทั้งหมดได้'
            : 'Guest mode: Clear all is disabled.'
          );
          return;
        }
        localStorage.removeItem(key());
      }
      function get(id){ return getAll().find(x => x.id === id); }

      window.MMMHistory = { __v:'3', save, getAll, get, remove, clear, isUser, __key: key };
    })();
  </script>

  <script>
    let chart;
    let lastInputs = null;
    let lastResults = null;

    // ===== i18n =====
    const langData = {
      en: {
        "page-title": "Retirement Planner",
        "label-currentAge": "Current Age",
        "label-retireAge": "Retirement Age",
        "label-currentSavings": "Current Savings (Baht)",
        "label-monthlyIncome": "Monthly Income (Baht)",
        "label-monthlyExpense": "Monthly Expenses (Baht)",
        "label-annualReturn": "Annual Return (%)",
        "btn-calculate": "Calculate",
        "btn-save-ret": "Save Calculation",
        "chartLabel": "Accumulated Savings (Baht)",
        "chartTitle": "Savings Growth Until Retirement",
        "xTitle": "Age (Years)",
        "yTitle": "Savings (Baht)",
        "savedMsg": "Saved!",
        "btn-back": "← Dashboard"
      },
      th: {
        "page-title": "เครื่องมือวางแผนเกษียณ",
        "label-currentAge": "อายุปัจจุบัน",
        "label-retireAge": "อายุเกษียณ",
        "label-currentSavings": "เงินออมปัจจุบัน (บาท)",
        "label-monthlyIncome": "รายได้ต่อเดือน (บาท)",
        "label-monthlyExpense": "ค่าใช้จ่ายต่อเดือนเฉลี่ย (บาท)",
        "label-annualReturn": "ผลตอบแทนต่อปี (%)",
        "btn-calculate": "คำนวณ",
        "btn-save-ret": "บันทึกการคำนวณ",
        "chartLabel": "เงินออมสะสม (บาท)",
        "chartTitle": "การเติบโตของเงินออมถึงอายุเกษียณ",
        "xTitle": "อายุ (ปี)",
        "yTitle": "เงินออมสะสม (บาท)",
        "savedMsg": "บันทึกแล้ว!",
        "btn-back": "← กลับหน้า Dashboard"
      }
    };

    // ===== format & parse with comma =====
    const fmt = (n)=> Number(n||0).toLocaleString(undefined, { maximumFractionDigits: 2 });
    function formatNumber(input){
      const raw = (input.value || "").toString().replace(/,/g,'');
      if (raw === "" || isNaN(raw)) return;
      const parts = raw.split('.');
      const intPart = parts[0];
      const decPart = parts[1] !== undefined ? '.' + parts[1] : '';
      input.value = Number(intPart).toLocaleString() + decPart;
    }
    const parseNum = (val)=> Number((val||"").toString().replace(/,/g,'')) || 0;

    function changeLanguage() {
      const lang = document.getElementById("lang-select").value;
      // persist language for cross pages / guest alerts
      localStorage.setItem('lang', lang);
      localStorage.setItem('language', lang);

      const t = langData[lang];
      for (const key in t) {
        const el = document.getElementById(key);
        if (el) el.innerText = t[key];
      }
      const labelAgeLookup = document.getElementById('label-ageLookup');
      if (labelAgeLookup) labelAgeLookup.innerText = (lang === 'th') ? 'ดูเงินออมสุทธิหลังหักค่าใช้จ่ายที่อายุ (ปี):' : 'View net savings after expenses at age:';
      calculate(); // refresh chart labels
    }

    function calculate() {
      const currentAge     = parseNum(document.getElementById("currentAge").value);
      const retireAge      = parseNum(document.getElementById("retireAge").value);
      const currentSavings = parseNum(document.getElementById("currentSavings").value);
      const monthlyIncome  = parseNum(document.getElementById("monthlyIncome").value);
      const monthlyExpense = parseNum(document.getElementById("monthlyExpense").value);
      const annualReturn   = parseNum(document.getElementById("annualReturn").value) / 100;

      const lang = document.getElementById("lang-select").value;
      const t = langData[lang];

      const workingYears = Math.max(0, retireAge - currentAge);
      let savings = currentSavings;
      let labels = [];
      let series = [];
      let totalContribution = 0;
      let totalInterest = 0;
      const yearlyDeposit = (monthlyIncome - monthlyExpense) * 12;

      // Accumulate until retirement
      for (let y = 1; y <= workingYears; y++) {
        savings += yearlyDeposit;
        totalContribution += Math.max(0, yearlyDeposit);
        const interest = Math.max(0, savings * annualReturn);
        savings += interest;
        totalInterest += interest;
        labels.push(currentAge + y);
        series.push(Number(savings.toFixed(2)));
      }

      // Post-retirement phase: withdraw monthlyExpense until money runs out
      let postLabels = [];
      let postSeries = [];
      let postRetireYears = 0;
      let postSavings = savings;
      let age = retireAge;
      let enough = true;
      while (postSavings > 0 && (age + postRetireYears) < 100) { // simulate until age 100
        postSavings -= monthlyExpense * 12;
        if (postSavings < 0) { postSavings = 0; enough = false; }
        postSavings += Math.max(0, postSavings * annualReturn); // interest on remaining
        postRetireYears++;
        postLabels.push(age + postRetireYears);
        postSeries.push(Number(postSavings.toFixed(2)));
      }

      // Age when money runs out
      let ageMoneyRunsOut = enough ? (age + postRetireYears) : (age + postRetireYears);

      lastInputs = {
        currentAge, retireAge, currentSavings,
        monthlyIncome, monthlyExpense, annualReturnPct: (annualReturn*100)
      };
      lastResults = {
        workingYears,
        monthlySurplus: (monthlyIncome - monthlyExpense),
        totalContribution: Number(totalContribution.toFixed(2)),
        totalInterest: Number(totalInterest.toFixed(2)),
        finalSavings: Number(savings.toFixed(2)),
        postRetireYears,
        ageMoneyRunsOut,
        enough
      };

      // Chart: pre-retirement (green), post-retirement (orange)
      const ctx = document.getElementById('retirementChart').getContext('2d');
      if (chart) chart.destroy();
      chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels.concat(postLabels),
          datasets: [
            {
              label: t.chartLabel + (t===langData.th ? ' (ก่อนเกษียณ)' : ' (Pre-Retirement)'),
              data: series,
              fill: true,
              backgroundColor: 'rgba(76,175,80,0.18)',
              borderColor: '#388e3c',
              tension: 0.2,
              pointRadius: 2
            },
            {
              label: t.chartLabel + (t===langData.th ? ' (หลังเกษียณ)' : ' (Post-Retirement)'),
              data: Array(series.length).fill(null).concat(postSeries),
              fill: true,
              backgroundColor: 'rgba(255,165,0,0.18)',
              borderColor: '#ff9800',
              tension: 0.2,
              pointRadius: 2
            }
          ]
        },
        options: {
          plugins: {
            legend: { position: 'bottom' },
            title: { display: true, text: t.chartTitle },
            tooltip: {
              callbacks: {
                label: function(ctx){
                  return `${t.chartLabel}: ${fmt(ctx.parsed.y)}`;
                }
              }
            }
          },
          scales: {
            x: { title: { display: true, text: t.xTitle }},
            y: {
              title: { display: true, text: t.yTitle },
              beginAtZero: true,
              ticks: { callback: (v)=> fmt(v) }
            }
          }
        }
      });

      // Age lookup logic
      const ageLookupInput = document.getElementById('ageLookup');
      const ageLookupResult = document.getElementById('ageLookupResult');
      if (ageLookupInput) {
        ageLookupInput.oninput = function() {
          const lookupAge = parseInt(ageLookupInput.value);
          let idx = labels.concat(postLabels).indexOf(lookupAge);
          let val = null;
          if (idx >= 0) {
            // Find which dataset
            if (idx < series.length) val = series[idx];
            else val = postSeries[idx - series.length];
          }
          // Subtract monthly expenses for that age if post-retirement
          let netVal = val;
          if (val !== null && !isNaN(val)) {
            if (idx >= series.length) {
              // post-retirement: already deducted in simulation, just show
              ageLookupResult.innerHTML = (t===langData.th ? 'เงินออมสุทธิ' : 'Net savings') + ' ' + lookupAge + ': <b>' + fmt(netVal) + '</b> ' + (t===langData.th ? 'บาท' : 'Baht');
            } else {
              // pre-retirement: show as is
              ageLookupResult.innerHTML = (t===langData.th ? 'เงินออม' : 'Savings') + ' ' + lookupAge + ': <b>' + fmt(netVal) + '</b> ' + (t===langData.th ? 'บาท' : 'Baht');
            }
          } else {
            ageLookupResult.innerHTML = (t===langData.th ? 'ไม่มีข้อมูล' : 'No data');
          }
        };
        // Auto show if value exists
        if (ageLookupInput.value) ageLookupInput.oninput();
      }

      // Show summary, advice, and formula below chart
      let summaryDiv = document.getElementById('retirement-summary');
      if (!summaryDiv) {
        summaryDiv = document.createElement('div');
        summaryDiv.id = 'retirement-summary';
        summaryDiv.style.margin = '24px auto 0 auto';
        summaryDiv.style.maxWidth = '700px';
        summaryDiv.style.textAlign = 'center';
        summaryDiv.style.fontSize = '1.08em';
        document.querySelector('.container').appendChild(summaryDiv);
      }
      let adviceMsg = '';
      if (enough) {
        adviceMsg = `<div style='background:#d1fae5;color:#065f46;border-radius:10px;padding:14px;font-weight:bold;margin-bottom:12px;'>${t===langData.th ? 'เงินออมเพียงพอสำหรับใช้จ่ายหลังเกษียณจนถึงอายุ ' + ageMoneyRunsOut + ' ปี' : 'Your savings are sufficient until age ' + ageMoneyRunsOut + '.'}</div>`;
      } else {
        // Actionable advice
        let suggestions = [
          t===langData.th ? 'เพิ่มเงินออมรายเดือนหรือปรับลดค่าใช้จ่าย' : 'Increase monthly savings or reduce expenses',
          t===langData.th ? 'พิจารณาเลื่อนอายุเกษียณออกไป' : 'Consider postponing retirement age',
          t===langData.th ? 'ปรับเปลี่ยนแผนการลงทุนเพื่อเพิ่มผลตอบแทน' : 'Adjust investment plan for higher returns',
          t===langData.th ? 'ลดค่าใช้จ่ายหลังเกษียณ' : 'Reduce post-retirement expenses',
        ];
        adviceMsg = `<div style='background:#fee2e2;color:#b91c1c;border-radius:10px;padding:14px;font-weight:bold;margin-bottom:10px;'>${t===langData.th ? 'เงินออมไม่เพียงพอสำหรับใช้จ่ายหลังเกษียณ' : 'Your savings are not sufficient for post-retirement expenses.'}</div>`+
          `<div style='background:#fff0f0;color:#b91c1c;border-radius:8px;padding:10px;font-size:1em;margin-bottom:16px;'>`+
          `<b>${t===langData.th ? 'คำแนะนำ' : 'Suggestions'}:</b><ul style='margin:8px 0 0 18px;'>`+
          suggestions.map(s=>`<li>${s}</li>`).join('')+
          `</ul></div>`;
      }
      // Formula and note
      let formulaMsg = `<div style='margin-top:18px;background:#f3f4f6;border-radius:10px;padding:14px;font-size:1em;color:#374151;'>`+
        `<b>${t===langData.th ? 'สูตรคำนวณเงินออมสะสม' : 'Savings Accumulation Formula'}:</b><br>`+
        `<span style='font-family:monospace;'>S = S<sub>0</sub> × (1 + r)<sup>n</sup> + PMT × [((1 + r)<sup>n</sup> - 1) / r]</span><br>`+
        `<span style='font-size:0.95em;'>${t===langData.th ? 'S = เงินออมสุดท้าย, S₀ = เงินออมเริ่มต้น, r = อัตราผลตอบแทนต่อปี, n = จำนวนปี, PMT = เงินออมต่อปี' : 'S = final savings, S₀ = initial savings, r = annual return rate, n = years, PMT = yearly savings'}</span>`+
        `<hr style='margin:12px 0;'>`+
        `<b>${t===langData.th ? 'หมายเหตุ' : 'Note'}:</b> ${t===langData.th ? 'สูตรนี้ไม่รวมผลกระทบจากภาษีและเงินเฟ้อ' : 'This formula does not account for taxes and inflation.'}`+
        `</div>`;
      summaryDiv.innerHTML =
        adviceMsg +
        `<div style='margin-bottom:10px;'>${t===langData.th ? 'เงินออมสุดท้าย ณ อายุเกษียณ' : 'Final savings at retirement'}: <b>${fmt(savings)}</b> ${t===langData.th ? 'บาท' : 'Baht'}</div>`+
        `<div style='margin-bottom:10px;'>${t===langData.th ? 'อายุที่เงินออมหมด' : 'Age when savings run out'}: <b>${ageMoneyRunsOut}</b></div>`+
        formulaMsg;
    }

    window.onload = () => {
      const savedLang = localStorage.getItem('lang') || localStorage.getItem('language') || 'en';
      document.getElementById("lang-select").value = savedLang;
      changeLanguage();
    };

    // -------- Back to Dashboard (prefer history, fallback to dashboard.php) --------
    (function(){
      const DASHBOARD = 'dashboard.php';
      document.getElementById('btn-back').addEventListener('click', ()=>{
        if (document.referrer) {
          history.back();
          setTimeout(()=>{
            if (!document.referrer || document.referrer === location.href) location.href = DASHBOARD;
          }, 200);
        } else {
          location.href = DASHBOARD;
        }
      });
    })();

    // -------- Save to History (blocked in Guest) --------
    document.getElementById("btn-save-ret").addEventListener("click", ()=>{
      if (!lastInputs || !lastResults) calculate(); // ensure latest
      const lang = document.getElementById("lang-select").value;
      const title = (lang === "th")
        ? `วางแผนเกษียณ @ อายุ ${lastInputs.retireAge.toLocaleString()}`
        : `Retirement @ ${lastInputs.retireAge.toLocaleString()}`;

      const id = MMMHistory.save({
        type: "retirement",
        title,
        inputs: lastInputs,
        results: lastResults,
        meta: { page: "retirement.php", version: "1.0" }
      });
      if (id) alert(langData[lang].savedMsg);
      // ถ้า Guest MMMHistory จะ alert ให้เอง และคืน null
    });
  </script>
</body>
</html>
