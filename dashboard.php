<?php /* Dashboard (Guest-aware + Account-scoped History) */ ?>
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
  <?php else: ?>
    localStorage.removeItem("mmm_session");
  <?php endif; ?>
</script>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>

  <style>
    body { font-family: 'Prompt', sans-serif; background: linear-gradient(to bottom right, #d2f1f9, #e6fcef); margin: 0; }
    .navbar { display: flex; justify-content: space-between; align-items: center; padding: 16px 32px; background: white; box-shadow: 0 2px 6px rgba(0,0,0,.05); }
    .navbar h1 { margin: 0; color: #10b981; font-size: 1.7rem; }
    .navbar .actions { display: flex; align-items: center; gap: 12px; }
    .navbar select, .navbar button { padding: 8px 12px; border-radius: 20px; border: none; font-size: 1rem; cursor: pointer; }
    .logout { background-color: #ef4444; color: white; }
    .history { background-color: #3b82f6; color: white; }
    .history[disabled] { opacity: .6; cursor: not-allowed; }

    /* Guest banner */
    .guest-strip { display:none; background:#fff7ed; border-bottom:1px solid #ffedd5; color:#9a3412; padding:10px 20px; font-size:.95rem; }
    .guest-strip .icon { margin-right:8px; }

    .dashboard { padding: 40px 60px 80px; }
    .main-headline { text-align: center; font-size: 2rem; color: #10b981; margin: 0 0 28px; font-weight: 700; }
    .section-title { font-size: 22px; color: #0f766e; margin: 36px 0 24px; }

    .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
    .card { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,.08); display: flex; flex-direction: column; align-items: center; text-align: center; transition: transform .2s ease; }
    .card:hover { transform: translateY(-5px); }
    .card img { max-width: 100%; height: 160px; object-fit: contain; margin-bottom: 12px; }
    .card-title { font-weight: bold; color: #10b981; font-size: 1.1rem; }
    .card-desc { font-size: .9rem; color: #555; margin: 8px 0 14px; }
    .card button { background-color: #10b981; border: none; color: white; padding: 10px 18px; font-size: 15px; border-radius: 6px; cursor: pointer; }

    /* ===== History Modal ===== */
    .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.35); display: none; align-items: center; justify-content: center; z-index: 50; }
    .modal { width: min(980px, 94vw); max-height: 90vh; overflow: hidden; background: #fff; border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,.25); display: flex; flex-direction: column; }
    .modal-header { padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
    .modal-header h3 { margin: 0; font-size: 1.25rem; color: #0f766e; }
    .modal-body { padding: 14px 20px 4px; overflow: auto; }
    .modal-footer { padding: 12px 20px 16px; border-top: 1px solid #eee; display: flex; gap: 10px; justify-content: flex-end; }
    .btn { padding: 8px 12px; border-radius: 8px; border: 1px solid #e5e7eb; background: #f9fafb; cursor: pointer; }
    .btn.primary { background: #10b981; color: #fff; border-color: #10b981; }
    .btn.danger { background: #ef4444; color: #fff; border-color: #ef4444; }
    .btn[disabled] { opacity: .6; cursor:not-allowed; }
    .toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 12px; }
    .toolbar input, .toolbar select { padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 8px; }
    .history-list { display: grid; grid-template-columns: 1fr; gap: 10px; }
    .item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center; background: #fafafa; }
    .item .meta { font-size: .9rem; color: #374151; }
    .item .meta .type { font-weight: 600; color: #0f766e; }
    .item .meta .title { font-weight: 600; color: #111827; }
    .item .actions { display: flex; gap: 8px; }
    .empty { padding: 20px; text-align: center; color: #6b7280; border: 1px dashed #d1d5db; border-radius: 12px; }

    /* ===== Detail Cards ===== */
    .detail { margin-top: 12px; display: none; }
    .detail-header { margin: 8px 0 12px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; background:#f8fafc; }
    .pill { display:inline-block; padding:4px 8px; border-radius:999px; background:#10b981; color:#fff; font-size:.8rem; margin-right:8px; }
    .metrics { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-top:10px; }
    .metric .k { font-size:.8rem; color:#6b7280; }
    .metric .v { font-size:1.05rem; font-weight:600; color:#111827; }
    .section-h { margin:14px 0 8px; font-weight:700; color:#0f766e; }
    .grid-cards { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:10px; }
    .kv { border: 1px solid #e5e7eb; border-radius:10px; padding:10px; background:#fff; }
    .kv .k { font-size:.82rem; color:#6b7280; }
    .kv .v { font-size:1rem; font-weight:600; color:#1f2937; }
    .raw-wrap { margin-top:12px; }
    pre.json { background:#0b1020; color:#e5e7eb; padding:12px; border-radius:10px; overflow:auto; max-height:40vh; font-size:.85rem; display:none; }
  </style>
</head>
<body>

<!--ซิงก์สถานะ login จาก PHP session เข้า localStorage ตั้งแต่เปิดหน้า -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

  if (isLoggedIn) {
    localStorage.setItem('mmm_session', 'user');
    localStorage.setItem('mmm_user', <?= json_encode($uname) ?>);
    localStorage.setItem('mmm_user_id', <?= json_encode($uid) ?>);
    localStorage.setItem('mmm_user_email', <?= json_encode($uemail) ?>);
  } else {
    localStorage.setItem('mmm_session', 'guest');
    localStorage.removeItem('mmm_user');
    localStorage.removeItem('mmm_user_id');
    localStorage.removeItem('mmm_user_email');
  }

  // เรียกใช้ UI
  applyGuestModeUI();
});
</script>


  <div class="navbar">
    <h1>MindMyMoney</h1>
    <div class="actions">
      <select id="lang-select" onchange="changeLanguage()">
        <option value="en">English</option>
        <option value="th">ไทย</option>
      </select>
      <button class="history" id="open-history"><i class="fas fa-clock"></i> <span id="btn-history">History</span></button>
      <!-- 🔁 NEW: ใช้ logout.php เพื่อเคลียร์ session ให้ถูกต้อง -->
      <button class="logout" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> <span id="btn-logout">Logout</span></button>
    </div>
  </div>

  <!-- Guest mode strip -->
  <div class="guest-strip" id="guest-strip"><i class="fa fa-user icon"></i><span id="guest-note">Guest mode: read-only. Log in to save/delete history.</span></div>

  <div class="dashboard">
    <h1 class="main-headline" id="headline">Interactive Calculators</h1>
    <div class="section-title" id="section-title">Essentials</div>

    <div class="card-grid">
      <div class="card">
        <i class="fas fa-user-clock" style="font-size:60px;color:#10b981;margin-bottom:12px;"></i>
        <div class="card-title" id="card1-title">Retirement Planning</div>
        <div class="card-desc" id="card1-desc">Calculate total assets using age, starting funds, income, expenses</div>
        <a href="retirement.php"><button id="card1-btn">Try Now</button></a>
      </div>
      <div class="card">
        <i class="fas fa-chart-line" style="font-size:60px;color:#0ea5e9;margin-bottom:12px;"></i>
        <div class="card-title" id="card2-title">Compound Interest</div>
        <div class="card-desc" id="card2-desc">Long-term compound interest growth</div>
        <a href="plan-compound.html"><button id="card2-btn">Try Now</button></a>
      </div>
      <div class="card">
        <i class="fas fa-car-side" style="font-size:60px;color:#f59e42;margin-bottom:12px;"></i>
        <div class="card-title" id="card3-title">Car Loan</div>
        <div class="card-desc" id="card3-desc">Debt and installment planning tool</div>
        <a href="plan-car.html"><button id="card3-btn">Try Now</button></a>
      </div>
      <div class="card">
        <i class="fas fa-home" style="font-size:60px;color:#6366f1;margin-bottom:12px;"></i>
        <div class="card-title" id="card4-title">Home Loan</div>
        <div class="card-desc" id="card4-desc">Mortgage planning with taxes & insurance</div>
        <a href="plan-home.html"><button id="card4-btn">Try Now</button></a>
      </div>
    </div>
  </div>

  <!-- ===== History Modal ===== -->
  <div class="modal-backdrop" id="history-backdrop" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="history-title">
      <div class="modal-header">
        <h3 id="history-title">Saved Calculations</h3>
        <button class="btn" id="close-history" aria-label="Close"><i class="fa fa-times"></i></button>
      </div>
      <div class="modal-body">
        <div class="toolbar">
          <input id="history-search" type="search" placeholder="Search title…">
          <select id="history-filter">
            <option value="all">All Types</option>
            <option value="retirement">Retirement</option>
            <option value="compound">Compound</option>
            <option value="car">Car Loan</option>
            <option value="home">Home Loan</option>
          </select>
          <span style="margin-left:auto"></span>
          <button class="btn danger" id="history-clear"><i class="fa fa-trash"></i> <span id="btn-clearall">Clear All</span></button>
        </div>

        <div id="history-list" class="history-list"></div>
        <div id="history-empty" class="empty" style="display:none">
          <span id="empty-text">No saved items yet. Run a calculator and tap “Save”.</span>
        </div>

        <!-- Detail -->
        <div id="history-detail" class="detail">
          <div class="detail-header">
            <span class="pill" id="detail-type">#type</span>
            <strong id="detail-title"></strong>
            <div style="margin-top:6px; color:#6b7280; font-size:.9rem;">
              <span id="detail-time"></span>
            </div>
            <div class="metrics" id="detail-metrics"></div>
          </div>

          <div class="section-h" id="h-inputs">Inputs</div>
          <div class="grid-cards" id="detail-inputs"></div>

          <div class="section-h" id="h-results">Results</div>
          <div class="grid-cards" id="detail-results"></div>

          <div class="raw-wrap">
            <button class="btn" id="toggle-raw"><i class="fa fa-code"></i> <span id="btn-raw">Show Raw JSON</span></button>
            <pre class="json" id="detail-json"></pre>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn primary" id="close-history-2"><span id="btn-close">Close</span></button>
      </div>
    </div>
  </div>

  <script>
    /* ============ i18n ============ */
    const langMap = {
      th: {
        "headline": "เครื่องคิดคำนวณแบบโต้ตอบ (Interactive Calculators)",
        "section-title": "พื้นฐาน",
        "btn-history": "ประวัติ",
        "btn-logout": "ออกจากระบบ",
        "card1-title": "วางแผนเกษียณ",
        "card1-desc": "คำนวณสินทรัพย์สะสม โดยใช้อายุเป้าหมาย เงินตั้งต้น รายรับ รายจ่าย",
        "card1-btn": "ลองดูทันที",
        "card2-title": "ดอกเบี้ยทบต้น",
        "card2-desc": "การเติบโตของดอกเบี้ยในระยะยาว",
        "card2-btn": "ลองดูทันที",
        "card3-title": "ผ่อนรถ",
        "card3-desc": "เครื่องมือวางแผนหนี้และค่างวดรถ",
        "card3-btn": "ลองดูทันที",
        "card4-title": "กู้บ้าน",
        "card4-desc": "วางแผนผ่อนบ้าน พร้อมภาษีและประกัน",
        "card4-btn": "ลองดูทันที",
        /* History modal */
        "history-title": "ประวัติการบันทึก",
        "btn-clearall": "ลบทั้งหมด",
        "empty-text": "ยังไม่มีรายการบันทึก ลองคำนวณแล้วกด “บันทึก”",
        "btn-close": "ปิด",
        "h-inputs": "ข้อมูลนำเข้า",
        "h-results": "ผลลัพธ์",
        "btn-raw": "แสดง Raw JSON",
        "saved": "บันทึกเมื่อ",
        "monthly": "ค่างวด/เดือน",
        "totalInterest": "ดอกเบี้ยรวม",
        "finalSavings": "ยอดสะสมปลายเกษียณ",
        "totalPaid": "ยอดชำระรวม",
        /* Guest */
        "guest-note": "โหมดผู้เยี่ยมชม: คำนวณได้เท่านั้น ต้องเข้าสู่ระบบเพื่อบันทึก/ลบประวัติ",
        "guest-readonly": "อ่านได้เท่านั้น (Guest)",
        "guest-nohistory": "โหมดผู้เยี่ยมชม: ไม่สามารถเปิดดูประวัติได้"
      },
      en: {
        "headline": "Interactive Calculators",
        "section-title": "Essentials",
        "btn-history": "History",
        "btn-logout": "Logout",
        "card1-title": "Retirement Planning",
        "card1-desc": "Calculate total assets using age, starting funds, income, expenses",
        "card1-btn": "Try Now",
        "card2-title": "Compound Interest",
        "card2-desc": "Long-term compound interest growth",
        "card2-btn": "Try Now",
        "card3-title": "Car Loan",
        "card3-desc": "Debt and installment planning tool",
        "card3-btn": "Try Now",
        "card4-title": "House Loan",
        "card4-desc": "Mortgage planning with taxes & insurance",
        "card4-btn": "Try Now",
        /* History modal */
        "history-title": "Saved Calculations",
        "btn-clearall": "Clear All",
        "empty-text": "No saved items yet. Run a calculator and tap “Save”.",
        "btn-close": "Close",
        "h-inputs": "Inputs",
        "h-results": "Results",
        "btn-raw": "Show Raw JSON",
        "saved": "Saved",
        "monthly": "Monthly",
        "totalInterest": "Total Interest",
        "finalSavings": "Final Savings",
        "totalPaid": "Total Paid",
        /* Guest */
        "guest-note": "Guest mode: read-only. Log in to save/delete history.",
        "guest-readonly": "Read-only (Guest)",
        "guest-nohistory": "Guest mode: History is disabled"
      }
    };

    function changeLanguage() {
      const lang = document.getElementById("lang-select").value;
      localStorage.setItem("lang", lang);
      localStorage.setItem("language", lang);

      const map = langMap[lang];
      for (const id in map) {
        const el = document.getElementById(id);
        if (el) el.innerText = map[id];
      }
      document.getElementById("history-search").placeholder = (lang === "th" ? "ค้นหาชื่อรายการ…" : "Search title…");
      const filter = document.getElementById("history-filter");
      filter.options[0].text = (lang==="th"?"ทั้งหมด":"All Types");
      filter.options[1].text = (lang==="th"?"เกษียณ":"Retirement");
      filter.options[2].text = (lang==="th"?"ดอกเบี้ยทบต้น":"Compound");
      filter.options[3].text = (lang==="th"?"ผ่อนรถ":"Car Loan");
      filter.options[4].text = (lang==="th"?"กู้บ้าน":"Home Loan");

      const guestNoteEl = document.getElementById("guest-note");
      if (guestNoteEl) guestNoteEl.innerText = map["guest-note"];

      applyGuestModeUI();
      renderHistory();
    }

    document.addEventListener("DOMContentLoaded", () => {
      const savedLang = localStorage.getItem("lang") || localStorage.getItem("language") || "en";
      localStorage.setItem("lang", savedLang);
      localStorage.setItem("language", savedLang);
      document.getElementById("lang-select").value = savedLang;
      changeLanguage();
      applyGuestModeUI();
    });

    /* ============ Shared History Library (Account-scoped, Guest-aware v3) ============ */
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

    /* ============ Dashboard modal interactions ============ */
    const $ = (q)=>document.querySelector(q);
    function openHistory(){ $("#history-backdrop").style.display = "flex"; renderHistory(); }
    function closeHistory(){
      $("#history-backdrop").style.display = "none";
      $("#history-detail").style.display = "none";
      $("#detail-json").textContent = "";
      $("#detail-title").textContent = "";
    }

    $("#close-history").addEventListener("click", closeHistory);
    $("#close-history-2").addEventListener("click", closeHistory);

    $("#history-search").addEventListener("input", renderHistory);
    $("#history-filter").addEventListener("change", renderHistory);
    $("#history-clear").addEventListener("click", ()=>{
      if (!MMMHistory.isUser()) return;
      if(confirm(localStorage.getItem("lang")==="th" ? "ลบรายการทั้งหมด?" : "Clear all items?")){
        MMMHistory.clear(); renderHistory();
      }
    });

    const money = (n)=> (n===undefined || n===null) ? "-" : new Intl.NumberFormat(undefined, {maximumFractionDigits:2}).format(n);

    function renderHistory(){
      const lang = localStorage.getItem("lang") || "en";
      const t = (k)=> (langMap[lang][k] || k);

      const q = $("#history-search").value.trim().toLowerCase();
      const type = $("#history-filter").value;
      const data = MMMHistory.getAll().filter(item=>{
        const okType = (type==="all" ? true : item.type===type);
        const okText = !q || (item.title||"").toLowerCase().includes(q);
        return okType && okText;
      });

      const listEl = $("#history-list");
      const emptyEl = $("#history-empty");
      listEl.innerHTML = "";

      const clearBtn = $("#history-clear");
      if (clearBtn) {
        clearBtn.disabled = !MMMHistory.isUser();
        clearBtn.title = MMMHistory.isUser() ? "" : (lang==='th' ? "อ่านได้เท่านั้น (Guest)" : "Read-only (Guest)");
      }

      if(data.length===0){ emptyEl.style.display = "block"; return; }
      emptyEl.style.display = "none";

      data.forEach(item=>{
        const div = document.createElement("div");
        div.className = "item";
        const created = new Date(item.createdAt).toLocaleString();

        const monthly = item.results?.monthlyPayment || item.results?.monthly || item.results?.monthlySurplus;
        const totalInt = item.results?.totalInterest;
        const finalSav = item.results?.finalSavings;
        const totalPaid = item.results?.totalPaid;

        div.innerHTML = `
          <div class="meta">
            <div class="title">${item.title || (lang==="th"?"ไม่มีชื่อ":"Untitled")}</div>
            <div>
              <span class="type">#${item.type}</span> • ${t("saved")}: ${created}
              ${monthly!=null ? ` • ${t("monthly")}: ${money(monthly)}` : ""}
              ${totalInt!=null ? ` • ${t("totalInterest")}: ${money(totalInt)}` : ""}
              ${finalSav!=null ? ` • ${t("finalSavings")}: ${money(finalSav)}` : ""}
              ${totalPaid!=null ? ` • ${t("totalPaid")}: ${money(totalPaid)}` : ""}
            </div>
          </div>
          <div class="actions">
            <button class="btn" data-act="view"><i class="fa fa-eye"></i> ${lang==="th"?"ดู":"View"}</button>
            <button class="btn danger" data-act="delete"><i class="fa fa-trash"></i> ${lang==="th"?"ลบ":"Delete"}</button>
          </div>
        `;

        div.querySelector('[data-act="view"]').addEventListener("click", ()=> showDetail(item));

        const delBtn = div.querySelector('[data-act="delete"]');
        if (!MMMHistory.isUser()) {
          delBtn.disabled = true;
          delBtn.title = (lang==='th' ? langMap.th["guest-readonly"] : langMap.en["guest-readonly"]);
          delBtn.addEventListener("click", (e)=>{ e.preventDefault(); return; });
        } else {
          delBtn.addEventListener("click", ()=>{
            if(confirm(lang==="th" ? "ลบรายการนี้?" : "Delete this item?")){
              MMMHistory.remove(item.id); renderHistory();
            }
          });
        }

        listEl.appendChild(div);
      });
    }

    function showDetail(item){
      const lang = localStorage.getItem("lang") || "en";
      const t = (k)=> (langMap[lang][k] || k);

      document.getElementById("history-detail").style.display = "block";
      document.getElementById("detail-title").textContent = item.title || (lang==="th"?"ไม่มีชื่อ":"Untitled");
      document.getElementById("detail-type").textContent = `#${item.type}`;
      document.getElementById("detail-time").textContent = `${t("saved")}: ` + new Date(item.createdAt).toLocaleString();

      const m = [];
      if(item.results?.monthlyPayment!=null) m.push({k:t("monthly"), v: money(item.results.monthlyPayment)});
      if(item.results?.totalInterest!=null) m.push({k:t("totalInterest"), v: money(item.results.totalInterest)});
      if(item.results?.finalSavings!=null) m.push({k:t("finalSavings"), v: money(item.results.finalSavings)});
      if(item.results?.totalPaid!=null) m.push({k:t("totalPaid"), v: money(item.results.totalPaid)});
      document.getElementById("detail-metrics").innerHTML = m.map(x=>`<div class="metric"><div class="k">${x.k}</div><div class="v">${x.v}</div></div>`).join("");

      const inputCards = [];
      for(const k in (item.inputs||{})){
        inputCards.push(`<div class="kv"><div class="k">${k}</div><div class="v">${money(item.inputs[k])}</div></div>`);
      }
      document.getElementById("detail-inputs").innerHTML = inputCards.join("") || `<div class="kv"><div class="v">${lang==="th"?"ไม่มีข้อมูล":"No data"}</div></div>`;

      const resultCards = [];
      for(const k in (item.results||{})){
        resultCards.push(`<div class="kv"><div class="k">${k}</div><div class="v">${money(item.results[k])}</div></div>`);
      }
      document.getElementById("detail-results").innerHTML = resultCards.join("") || `<div class="kv"><div class="v">${lang==="th"?"ไม่มีข้อมูล":"No data"}</div></div>`;

      document.getElementById("detail-json").textContent = JSON.stringify(item, null, 2);
      const btnRaw = document.getElementById("toggle-raw");
      const pre = document.getElementById("detail-json");
      const labelShow = langMap[lang]["btn-raw"];
      const labelHide = (lang==="th" ? "ซ่อน Raw JSON" : "Hide Raw JSON");
      btnRaw.onclick = ()=>{
        const visible = pre.style.display === "block";
        pre.style.display = visible ? "none" : "block";
        document.getElementById("btn-raw").textContent = visible ? labelShow : labelHide;
      };
      document.getElementById("btn-raw").textContent = labelShow;
      pre.style.display = "none";
    }

    // ปุ่ม History: ปิดการใช้งานถ้าเป็น guest + แยกประวัติตามบัญชีด้วย MMMHistory v3
    function applyGuestModeUI(){
      const isGuest = (localStorage.getItem('mmm_session') !== 'user');
      const strip = document.getElementById('guest-strip');
      strip.style.display = isGuest ? 'block' : 'none';

      const lang = localStorage.getItem('lang') || 'en';
      const openBtn = document.getElementById('open-history');

      // รีเซ็ตก่อน
      openBtn.replaceWith(openBtn.cloneNode(true));
      const newOpenBtn = document.getElementById('open-history');

      if (isGuest) {
        newOpenBtn.disabled = true;
        newOpenBtn.title = (lang==='th' ? langMap.th["guest-nohistory"] : langMap.en["guest-nohistory"]);
        newOpenBtn.addEventListener('click', (e)=>{ e.preventDefault(); return false; });
      } else {
        newOpenBtn.disabled = false;
        newOpenBtn.title = "";
        newOpenBtn.addEventListener('click', openHistory);
      }
    }
  </script>

  
</body>
</html>
