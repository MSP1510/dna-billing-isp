<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>D.N.A_Billing | D.N.A_NETWORK by MSP_NET</title>
<link rel="manifest" href="/manifest.json"><meta name="theme-color" content="#00a65a"><link rel="icon" href="/assets/icon-192.png">
<script>if("serviceWorker" in navigator){navigator.serviceWorker.register("/sw.js");}</script>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',sans-serif}
body{display:flex;min-height:100vh;background:#f1f5f9;color:#1e293b}
.side{width:230px;background:#222d32;color:#b8c7ce;display:flex;flex-direction:column;flex-shrink:0;transition:transform 0.25s ease;z-index:1000}
.brand{background:#1a2226;padding:15px;color:#fff;font-weight:800;font-size:16px;border-bottom:1px solid #2c3b41;display:flex;justify-content:space-between;align-items:center}
.brand small{display:block;font-size:10px;color:#38bdf8;font-weight:600}
.close-btn{display:none;background:none;border:none;color:#fff;font-size:20px;cursor:pointer}
.nav-head{padding:12px 15px 6px;font-size:11px;color:#6c818c;text-transform:uppercase;font-weight:700}
.menu{overflow-y:auto;flex:1}
.menu a{display:flex;justify-content:space-between;align-items:center;padding:11px 15px;color:#b8c7ce;text-decoration:none;font-size:13px;border-left:3px solid transparent}
.menu a:hover,.menu a.act{background:#1e282c;color:#fff;border-left-color:#00a65a}
.badge-new{background:#00a65a;color:#fff;font-size:10px;padding:2px 6px;border-radius:4px;font-weight:700}
.main{flex:1;display:flex;flex-direction:column;overflow-x:auto;width:100%}
.top{background:#fff;padding:10px 14px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;gap:8px;position:sticky;top:0;z-index:900}
.menu-btn{display:none;background:#222d32;color:#fff;border:none;padding:7px 11px;border-radius:6px;font-size:16px;cursor:pointer}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:950}
.wrap{padding:16px}
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:20px}
.tile{border-radius:6px;padding:18px;color:#fff;box-shadow:0 2px 6px rgba(0,0,0,0.1)}
.t-green{background:#00a65a}.t-yellow{background:#f39c12}.t-blue{background:#00c0ef}.t-red{background:#dd4b39}
.tile h2{font-size:26px;margin-bottom:4px}.tile p{font-size:11px;text-transform:uppercase;font-weight:600;opacity:0.9}
.card{background:#fff;border-radius:6px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,0.08);margin-bottom:16px}
input,select{padding:8px 10px;border:1px solid #cbd5e1;border-radius:4px;font-size:13px;margin:3px}
.btn{padding:8px 12px;border:none;border-radius:4px;background:#0284c7;color:#fff;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.btn-g{background:#00a65a}.btn-r{background:#dd4b39}.btn-w{background:#f39c12}
table{width:100%;border-collapse:collapse;margin-top:12px;font-size:13px}
th,td{padding:9px;border-bottom:1px solid #e2e8f0;text-align:left}th{background:#f8fafc;color:#475569}

/* LOADER LOGO MINI BERPUTAR */
#dnaLoader{position:fixed;inset:0;background:rgba(241,245,249,0.72);backdrop-filter:blur(2px);z-index:9999;display:flex;align-items:center;justify-content:center;transition:opacity 0.2s}
.spin-logo{width:26px;height:26px;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.2);animation:putarLogo 0.9s linear infinite}
@keyframes putarLogo{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}

@media(max-width:768px){
  .side{position:fixed;top:0;bottom:0;left:0;transform:translateX(-100%)}
  .side.open{transform:translateX(0)}
  .overlay.open{display:block}
  .menu-btn,.close-btn{display:inline-block}
}





@media(min-width:769px){}



#dnaTopHeader{position:fixed !important;top:0 !important;left:0 !important;right:0 !important;z-index:990 !important;background:#fff !important;box-shadow:0 2px 10px rgba(0,0,0,0.12) !important;padding:10px 12px !important;margin:0 !important;}
body{padding-top:66px !important;}
@media(min-width:769px){#dnaTopHeader{left:230px !important;}}

#dnaLiveClock{background:linear-gradient(135deg,#0284c7,#1e40af) !important;color:#ffffff !important;border:1px solid rgba(125,211,252,0.45) !important;border-radius:10px !important;padding:5px 10px !important;text-align:center !important;line-height:1.15 !important;box-shadow:0 3px 10px rgba(2,132,199,0.28) !important;font-variant-numeric:tabular-nums !important;white-space:nowrap !important;}
@media(max-width:480px){#dnaTopHeader h1,#dnaTopHeader .page-title{font-size:13px !important;}#dnaTopHeader a[href*='logout']{padding:6px 8px !important;font-size:11px !important;}}

@media(max-width:480px){#dnaTopHeader h1,#dnaTopHeader .ttl{font-size:13px !important;white-space:nowrap !important;}}


/* Luxury Bright & Dynamic Gradient Theme */
body{background:linear-gradient(180deg,#f8fafc 0%,#e2e8f0 100%) !important;color:#0f172a !important;}
#dnaTopHeader{background:rgba(255,255,255,0.92) !important;backdrop-filter:blur(14px) !important;border-bottom:1px solid #cbd5e1 !important;box-shadow:0 4px 16px rgba(15,23,42,0.06) !important;color:#0f172a !important;}
#dnaTopHeader h1,#dnaTopHeader .ttl{color:#0f172a !important;font-weight:800 !important;}
#dnaTopHeader small{color:#475569;} #jamDigital,#jamDigital *{color:#ffffff !important;}
.menu-btn{background:#f1f5f9 !important;color:#0f172a !important;border:1px solid #cbd5e1 !important;}
.side{background:linear-gradient(180deg,#ffffff 0%,#f1f5f9 100%) !important;border-right:1px solid #cbd5e1 !important;box-shadow:4px 0 24px rgba(15,23,42,0.08) !important;}
.side .brand{background:linear-gradient(135deg,#0284c7,#1d4ed8) !important;color:#ffffff !important;}
.side .brand small{color:#e0f2fe !important;}
.side .menu a{color:#334155 !important;font-weight:600 !important;}
.side .menu a:hover,.side .menu a.act,.side .menu a.active{background:linear-gradient(135deg,#0284c7,#2563eb) !important;color:#ffffff !important;border-radius:10px !important;box-shadow:0 4px 12px rgba(37,99,235,0.25) !important;}
.card,.box,.panel,.dna-card{background:#ffffff !important;border:1px solid #e2e8f0 !important;border-radius:18px !important;color:#0f172a !important;box-shadow:0 8px 24px rgba(15,23,42,0.06) !important;}
.card h2,.card h3,.card h4,.box h3,.dna-card h3{color:#0284c7 !important;font-weight:800 !important;}
table{color:#1e293b !important;}
table th{background:linear-gradient(135deg,#0284c7,#1e40af) !important;color:#ffffff !important;border:none !important;}
table td{border-bottom:1px solid #e2e8f0 !important;color:#1e293b !important;}
tr:hover td{background:#f0f9ff !important;}
input,select,textarea{background:#f8fafc !important;color:#0f172a !important;border:1.5px solid #cbd5e1 !important;border-radius:10px !important;}
input:focus,select:focus,textarea:focus{border-color:#0284c7 !important;background:#ffffff !important;box-shadow:0 0 0 3px rgba(2,132,199,0.15) !important;}
label{color:#334155 !important;font-weight:700 !important;}
</style></head><body>

<div id="dnaLoader"><img src="/assets/icon-192.png" class="spin-logo" alt="Loading"></div>
<div class="overlay" id="navOverlay" onclick="toggleMenu()"></div>

<div class="side" id="sideNav">
  <div class="brand">
    <div>D.N.A_Billing<small>D.N.A_NETWORK by MSP_NET</small></div>
    <button class="close-btn" onclick="toggleMenu()">&times;</button>
  </div>
  <div class="nav-head">Navigation Utama</div>
  <div class="menu">
    <?php if(punyaAkses('dashboard')): ?><a class="<?= $page=='dashboard'?'act':'' ?>" href="?page=dashboard">Dashboard <span class="badge-new">new</span></a><?php endif; ?>
    <?php if(punyaAkses('session')): ?><a class="<?= $page=='session'?'act':'' ?>" href="?page=session">Session User</a>
    <?php if(punyaAkses('monitor')): ?><a class="<?= $page=='monitor'?'act':'' ?>" href="?page=monitor">Monitor Online</a><?php endif; ?><?php endif; ?>
    <?php if(punyaAkses('remote')): ?><a class="<?= $page=='remote'?'act':'' ?>" href="?page=remote">Remote Modem</a><?php endif; ?>
    <?php if(punyaAkses('router')): ?><a class="<?= $page=='router'?'act':'' ?>" href="?page=router">Router [ NAS ]</a><?php endif; ?>
    <?php if(punyaAkses('odp')): ?><a class="<?= $page=='odp'?'act':'' ?>" href="?page=odp">Data ODP | POP</a><?php endif; ?>
    <?php if(punyaAkses('paket')): ?><a class="<?= $page=='paket'?'act':'' ?>" href="?page=paket">Profil Paket</a><?php endif; ?>
    <?php if(punyaAkses('pelanggan')): ?><a class="<?= $page=='pelanggan'?'act':'' ?>" href="?page=pelanggan">List Pelanggan</a><?php endif; ?>
    <?php if(punyaAkses('tagihan')): ?><a class="<?= $page=='tagihan'?'act':'' ?>" href="?page=tagihan">Invoice & WA</a><?php endif; ?>
    <?php if(punyaAkses('keuangan')): ?><a class="<?= $page=='keuangan'?'act':'' ?>" href="?page=keuangan">Keuangan & Kas</a><?php endif; ?>
    <?php if(punyaAkses('aset')): ?><a class="<?= $page=='aset'?'act':'' ?>" href="?page=aset">Inventaris Aset</a><?php endif; ?>
    <?php if(punyaAkses('voucher')): ?><a class="<?= $page=='voucher'?'act':'' ?>" href="?page=voucher">Kartu Voucher</a>
    <?php if(punyaAkses('mitra')): ?><a class="<?= $page=='mitra'?'act':'' ?>" href="?page=mitra">Mitra / Reseller</a><?php endif; ?><?php endif; ?>
    <?php if(punyaAkses('tiket')): ?><a class="<?= $page=='tiket'?'act':'' ?>" href="?page=tiket">Tiket Laporan</a><?php endif; ?>
    <?php if(punyaAkses('neighbor')): ?><a class="<?= $page=='neighbor'?'act':'' ?>" href="?page=neighbor">List Neighbor</a><?php endif; ?>
    <?php if($role=='owner'): ?>
      <a class="<?= $page=='users'?'act':'' ?>" href="?page=users">Manajemen User</a>
      <a class="<?= $page=='logs'?'act':'' ?>" href="?page=logs">Log Aktivitas</a>
      <a class="<?= $page=='script_mt'?'act':'' ?>" href="?page=script_mt">Script MikroTik</a>
      <a class="<?= $page=='backup'?'act':'' ?>" href="?page=backup">Backup & Restore</a>
      <a class="<?= $page=='tools'?'act':'' ?>" href="?page=tools">Tool Sistem</a>
<a href="?page=panduan" class="<?= ($page??'')=='panduan'?'active':'' ?>">Panduan &amp; Tentang</a>
    <?php endif; ?>
  </div>
</div>

<div class="main">
  <div id="dnaTopHeader" class="top">
    <div style="display:flex;align-items:center;gap:8px">
      <button class="menu-btn" onclick="toggleMenu()">&#9776;</button>
      <div><b style="font-size:13px"><?= strtoupper($page) ?></b><br><small style="font-size:10px;color:#64748b"><?= $_SESSION['nama'] ?> (<?= strtoupper($role) ?>)</small></div>
    </div>
    <div style="display:flex;align-items:center;gap:6px">
      <div id="jamDigital" style="background:linear-gradient(135deg,#0284c7,#1d4ed8);color:#ffffff;padding:5px 10px;border-radius:10px;border:1px solid rgba(186,230,253,0.5);box-shadow:0 3px 10px rgba(2,132,199,0.3);text-align:center;line-height:1.15;white-space:nowrap;font-variant-numeric:tabular-nums">--:--:--</div>
      <a href="?logout=1" class="btn btn-r" style="padding:6px 8px;font-size:11px;white-space:nowrap">Logout</a>
    </div>
  </div>
  <div class="wrap">
<script>
function toggleMenu(){
  document.getElementById('sideNav').classList.toggle('open');
  document.getElementById('navOverlay').classList.toggle('open');
}
function updJam(){
  const n = new Date();
  const h = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][n.getDay()];
  const b = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][n.getMonth()];
  const p = v => String(v).padStart(2,'0');
  const tgl = `${h}, ${p(n.getDate())} ${b} ${n.getFullYear()}`;
  const wkt = `${p(n.getHours())}:${p(n.getMinutes())}:${p(n.getSeconds())}`;
  document.getElementById('jamDigital').innerHTML = `<div style="font-size:9.5px;opacity:0.92;font-weight:600">${tgl}</div><div style="font-size:12.5px;font-weight:800;letter-spacing:0.5px">${wkt} <span style="font-size:9px;opacity:0.85">WIB</span></div>`;
}
updJam(); setInterval(updJam, 1000);
window.addEventListener('load', () => { document.getElementById('dnaLoader').style.display = 'none'; });
document.querySelectorAll('.menu a').forEach(a => a.addEventListener('click', () => { document.getElementById('dnaLoader').style.display = 'flex'; }));
</script>
