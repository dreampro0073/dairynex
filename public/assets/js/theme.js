/* ============================================================
   SUPER ADMIN + DAIRY ADMIN (desktop shells)
   ============================================================ */
const superNav = [
  {group:null, items:[
    {key:'clients', ic:'◧', label:'Clients / Dairies'},
    {key:'billing', ic:'₹', label:'Subscription & Billing'},
    {key:'databases', ic:'◫', label:'Client Databases'},
    {key:'plans', ic:'▤', label:'Plans'},
    {key:'usage', ic:'◔', label:'Usage Statistics'},
  ]},
  {group:'SYSTEM', items:[
    {key:'support', ic:'✆', label:'Support'},
    {key:'settings', ic:'⚙', label:'Settings'},
  ]}
];
const dairyNav = [
  {group:null, items:[
    {key:'dashboard', ic:'◧', label:'Dashboard'},
    {key:'customers', ic:'☺', label:'Customers'},
    {key:'products', ic:'◍', label:'Products & Rates'},
    {key:'subscriptions', ic:'↻', label:'Subscriptions'},
    {key:'routes', ic:'⚑', label:'Delivery Routes'},
    {key:'deliveryboys', ic:'🛵', label:'Delivery Boys'},
    {key:'dailydeliveries', ic:'▦', label:'Daily Deliveries'},
    {key:'otp', ic:'✆', label:'OTP Verification'},
  ]},
  {group:'MONEY', items:[
    {key:'ledger', ic:'▤', label:'Customer Ledger'},
    {key:'credit', ic:'₹', label:'Credit / उधार'},
    {key:'emi', ic:'◷', label:'EMI / Payment Plans'},
    {key:'payments', ic:'✓', label:'Payments'},
    {key:'billingm', ic:'▧', label:'Monthly Billing'},
  ]},
  {group:'MORE', items:[
    {key:'inventory', ic:'◫', label:'Inventory'},
    {key:'reports', ic:'▥', label:'Reports'},
    {key:'whatsapp', ic:'💬', label:'WhatsApp / SMS'},
    {key:'settings', ic:'⚙', label:'Settings'},
  ]}
];

const superTitles = {clients:['Clients','128 dairies onboarded · 6 new this month','+ Add Client'],
  billing:['Subscription & Billing','Platform-wide invoices to each client','+ Create Invoice'],
  databases:['Client Databases','Storage & backup status per client','Run Backup Now'],
  plans:['Plans','Basic · Standard · Premium tiers','+ New Plan'],
  usage:['Usage Statistics','Activity across all clients this month','Export Report'],
  support:['Support','Open tickets from client dairies','+ New Ticket'],
  settings:['Platform Settings','Global configuration for DoodhKhata','Save Changes'],
  'clients-add':['Add New Client','Onboard a new dairy business',''],
  'clients-edit':['Edit Client','Update client details','']};

const dairyTitles = {dashboard:['Dashboard','Friday, 5 September 2026',"Start Today's Round"],
  customers:['Customers','1,240 active customers across 8 routes','+ Add Customer'],
  products:['Products & Rates','7 products · rates last updated 2 days ago','+ Add Product'],
  subscriptions:['Subscriptions','1,190 active · 38 paused · 12 cancelled','+ New Subscription'],
  routes:['Delivery Routes','8 routes covering 1,240 stops','+ Add Route'],
  deliveryboys:['Delivery Boys','18 staff on roll · 16 on duty today','+ Add Delivery Boy'],
  dailydeliveries:["Daily Deliveries",'Friday, 5 September 2026 · 312 of 340 done','Export Sheet'],
  otp:['OTP Verification','SMS + WhatsApp OTP delivery confirmation','Configure'],
  ledger:['Customer Ledger','Running balance per customer','Export Ledger'],
  credit:['Credit / उधार','₹86,050 outstanding across 42 customers','Send Reminders'],
  emi:['EMI / Payment Plans','Equipment deposits & instalment plans','+ New Plan'],
  payments:['Payments','Cash & UPI collections, reconciled daily','Reconcile Today'],
  billingm:['Monthly Billing','September bills for 1,240 customers','Generate All Bills'],
  inventory:['Inventory','Opening / closing stock by product','+ Stock Entry'],
  reports:['Reports','Revenue, collection efficiency & route performance','Download PDF'],
  whatsapp:['WhatsApp / SMS','Automated templates for bills & reminders','+ New Template'],
  settings:['Settings','Business profile & notification preferences','Save Changes'],
  'customers-add':['Add New Customer','Create a new customer profile',''],
  'customers-edit':['Edit Customer','Update customer details',''],
  'products-add':['Add New Product','Add a product to your catalogue',''],
  'subscriptions-add':['New Subscription','Set up a recurring subscription',''],
  'routes-add':['Add New Route','Create a delivery route',''],
  'deliveryboys-add':['Add Delivery Boy','Onboard a new delivery staff member',''],
  'deliveryboys-edit':['Edit Delivery Boy','Update staff details','']};

const S = {};
const D = {};

S.clients = `
<div class="stat-row">
  <div class="stat"><div class="label">Active Clients</div><div class="value">114</div><div class="sub up">↑ 6 this month</div></div>
  <div class="stat blue"><div class="label">Total Customers Served</div><div class="value">41,280</div><div class="sub">across all clients</div></div>
  <div class="stat butter"><div class="label">MRR</div><div class="value">₹6.8L</div><div class="sub up">↑ 4.2% vs last month</div></div>
  <div class="stat danger"><div class="label">Payment Overdue</div><div class="value">9</div><div class="sub down">clients past due date</div></div>
</div>
<div class="panel">
  <div class="panel-head"><h3>All Clients</h3><span class="link" onclick="loadSuper('clients-add')">+ Add Client</span></div>
  <table><thead><tr><th>Dairy / Client</th><th>Plan</th><th>Customers</th><th>Delivery Boys</th><th>Status</th><th class="num">Next Billing</th><th></th></tr></thead>
  <tbody>
    <tr><td><div class="client-row-name">Shree Krishna Dairy</div><div class="client-row-sub">Haridwar, UK</div></td><td><span class="plan-chip premium">Premium</span></td><td>1,240</td><td>18</td><td><span class="status-dot active"></span>Active</td><td class="num">05 Oct 2026</td><td><span class="link" onclick="loadSuper('clients-edit')">Edit</span></td></tr>
    <tr><td><div class="client-row-name">Amul Fresh Distributors</div><div class="client-row-sub">Rishikesh, UK</div></td><td><span class="plan-chip standard">Standard</span></td><td>860</td><td>11</td><td><span class="status-dot active"></span>Active</td><td class="num">12 Oct 2026</td><td><span class="link" onclick="loadSuper('clients-edit')">Edit</span></td></tr>
    <tr><td><div class="client-row-name">Ganga Milk Suppliers</div><div class="client-row-sub">Dehradun, UK</div></td><td><span class="plan-chip basic">Basic</span></td><td>310</td><td>5</td><td><span class="status-dot inactive"></span>Inactive</td><td class="num">— overdue —</td><td><span class="link" onclick="loadSuper('clients-edit')">Edit</span></td></tr>
    <tr><td><div class="client-row-name">Nandini Dairy Farm</div><div class="client-row-sub">Meerut, UP</div></td><td><span class="plan-chip premium">Premium</span></td><td>2,105</td><td>26</td><td><span class="status-dot active"></span>Active</td><td class="num">01 Oct 2026</td><td><span class="link" onclick="loadSuper('clients-edit')">Edit</span></td></tr>
    <tr><td><div class="client-row-name">Verka Home Delivery</div><div class="client-row-sub">Ludhiana, PB</div></td><td><span class="plan-chip standard">Standard</span></td><td>640</td><td>9</td><td><span class="status-dot active"></span>Active</td><td class="num">18 Oct 2026</td><td><span class="link" onclick="loadSuper('clients-edit')">Edit</span></td></tr>
  </tbody></table>
</div>`;

S.billing = `
<div class="stat-row">
  <div class="stat"><div class="label">Invoiced this month</div><div class="value">₹7.1L</div><div class="sub">114 clients</div></div>
  <div class="stat blue"><div class="label">Collected</div><div class="value">₹6.4L</div><div class="sub up">90% collection rate</div></div>
  <div class="stat butter"><div class="label">Pending</div><div class="value">₹58,200</div><div class="sub">15 invoices</div></div>
  <div class="stat danger"><div class="label">Overdue &gt; 15 days</div><div class="value">9</div><div class="sub down">needs follow-up</div></div>
</div>
<div class="panel"><div class="panel-head"><h3>Client Invoices</h3><span class="link">Export</span></div>
<table><thead><tr><th>Client</th><th>Plan</th><th>Invoice #</th><th>Period</th><th>Status</th><th class="num">Amount</th></tr></thead>
<tbody>
  <tr><td class="client-row-name">Shree Krishna Dairy</td><td><span class="plan-chip premium">Premium</span></td><td>INV-2026-118</td><td>Sep 2026</td><td><span class="badge ok">Paid</span></td><td class="num">₹4,999</td></tr>
  <tr><td class="client-row-name">Amul Fresh Distributors</td><td><span class="plan-chip standard">Standard</span></td><td>INV-2026-119</td><td>Sep 2026</td><td><span class="badge pending">Pending</span></td><td class="num">₹2,499</td></tr>
  <tr><td class="client-row-name">Ganga Milk Suppliers</td><td><span class="plan-chip basic">Basic</span></td><td>INV-2026-104</td><td>Aug 2026</td><td><span class="badge due">Overdue</span></td><td class="num">₹999</td></tr>
  <tr><td class="client-row-name">Nandini Dairy Farm</td><td><span class="plan-chip premium">Premium</span></td><td>INV-2026-120</td><td>Sep 2026</td><td><span class="badge ok">Paid</span></td><td class="num">₹4,999</td></tr>
</tbody></table></div>`;

S.databases = `
<div class="cards-grid">
  ${['Shree Krishna Dairy — 1.4 GB — Backed up 2h ago','Amul Fresh Distributors — 860 MB — Backed up 4h ago','Ganga Milk Suppliers — 210 MB — Backup failed','Nandini Dairy Farm — 2.1 GB — Backed up 1h ago','Verka Home Delivery — 640 MB — Backed up 3h ago','Mahaan Milk Co-op — 980 MB — Backed up 5h ago']
  .map(t=>{const [name,size,status]=t.split(' — ');const bad=status.includes('failed');
    return `<div class="info-card"><div class="h">${name}</div><div class="s">${size} used</div><span class="badge ${bad?'due':'ok'}">${status}</span></div>`}).join('')}
</div>`;

S.plans = `
<div class="grid-3">
  <div class="plan-card"><div class="pname">Basic</div><div class="pprice">₹999<span>/mo</span></div>
    <ul><li>Up to 300 customers</li><li>1 delivery route</li><li>SMS OTP verification</li><li>Basic reports</li></ul>
    <button class="btn secondary" style="width:100%;">Edit Plan</button></div>
  <div class="plan-card highlight"><div class="pname">Standard</div><div class="pprice">₹2,499<span>/mo</span></div>
    <ul><li>Up to 1,000 customers</li><li>Up to 5 routes</li><li>WhatsApp + SMS OTP</li><li>Ledger & credit tracking</li><li>Monthly auto-billing</li></ul>
    <button class="btn" style="width:100%;">Edit Plan</button></div>
  <div class="plan-card"><div class="pname">Premium</div><div class="pprice">₹4,999<span>/mo</span></div>
    <ul><li>Unlimited customers</li><li>Unlimited routes</li><li>EMI & payment plans</li><li>Inventory management</li><li>Priority support</li></ul>
    <button class="btn secondary" style="width:100%;">Edit Plan</button></div>
</div>`;

S.usage = `
<div class="stat-row">
  <div class="stat"><div class="label">Daily active dairies</div><div class="value">98</div><div class="sub up">of 114 total</div></div>
  <div class="stat blue"><div class="label">Deliveries logged today</div><div class="value">28,410</div><div class="sub">platform-wide</div></div>
  <div class="stat butter"><div class="label">OTPs sent today</div><div class="value">19,220</div><div class="sub">via SMS + WhatsApp</div></div>
  <div class="stat danger"><div class="label">Support tickets open</div><div class="value">6</div><div class="sub down">2 high priority</div></div>
</div>
<div class="panel"><div class="panel-head"><h3>Top Clients by Activity</h3></div>
<div>
  <div class="bar-row"><div class="bl">Nandini Dairy Farm</div><div class="bar-track"><div class="bar-fill" style="width:92%"></div></div><div class="bv">92%</div></div>
  <div class="bar-row"><div class="bl">Shree Krishna Dairy</div><div class="bar-track"><div class="bar-fill" style="width:87%"></div></div><div class="bv">87%</div></div>
  <div class="bar-row"><div class="bl">Verka Home Delivery</div><div class="bar-track"><div class="bar-fill" style="width:74%"></div></div><div class="bv">74%</div></div>
  <div class="bar-row"><div class="bl">Amul Fresh Distributors</div><div class="bar-track"><div class="bar-fill" style="width:69%"></div></div><div class="bv">69%</div></div>
  <div class="bar-row"><div class="bl">Ganga Milk Suppliers</div><div class="bar-track"><div class="bar-fill" style="width:31%"></div></div><div class="bv">31%</div></div>
</div></div>`;

S.support = `
<div class="panel"><div class="panel-head"><h3>Open Tickets</h3><span class="link">View closed</span></div>
<table><thead><tr><th>Ticket</th><th>Client</th><th>Issue</th><th>Priority</th><th class="num">Raised</th></tr></thead>
<tbody>
  <tr><td>#4021</td><td>Ganga Milk Suppliers</td><td>Backup failing since 3 days</td><td><span class="badge due">High</span></td><td class="num">2h ago</td></tr>
  <tr><td>#4019</td><td>Verka Home Delivery</td><td>WhatsApp template not sending</td><td><span class="badge due">High</span></td><td class="num">5h ago</td></tr>
  <tr><td>#4015</td><td>Amul Fresh Distributors</td><td>Need extra delivery-boy seat</td><td><span class="badge pending">Medium</span></td><td class="num">1 day ago</td></tr>
  <tr><td>#4009</td><td>Mahaan Milk Co-op</td><td>Rate change not reflecting in app</td><td><span class="badge pending">Medium</span></td><td class="num">2 days ago</td></tr>
</tbody></table></div>`;

S.settings = `
<div class="panel"><div class="panel-head"><h3>Platform Profile</h3></div>
<div class="form-grid">
  <div class="field"><label>Platform Name</label><input value="DoodhKhata"></div>
  <div class="field"><label>Support Email</label><input value="support@doodhkhata.in"></div>
  <div class="field"><label>Default Currency</label><select><option>INR (₹)</option></select></div>
  <div class="field"><label>Default Timezone</label><select><option>Asia/Kolkata</option></select></div>
  <div class="field full"><label>Platform Notice (shown to all clients)</label><textarea>Scheduled maintenance on 12 Sep, 2–3 AM IST.</textarea></div>
</div></div>
<div class="panel"><div class="panel-head"><h3>Notification Channels</h3></div>
  <div class="toggle-row"><div><div class="t-h">SMS gateway alerts</div><div class="t-s">Notify on delivery failures</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">WhatsApp Business API</div><div class="t-s">Connected — Meta verified</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">New client auto-welcome email</div><div class="t-s">Send onboarding guide automatically</div></div><div class="switch"></div></div>
</div>`;

D.dashboard = `
<div class="stat-row">
  <div class="stat"><div class="label">Today's Deliveries</div><div class="value">312 / 340</div><div class="sub up">92% complete</div></div>
  <div class="stat blue"><div class="label">Active Customers</div><div class="value">1,240</div><div class="sub">across 8 routes</div></div>
  <div class="stat butter"><div class="label">Collected Today</div><div class="value">₹18,420</div><div class="sub">Cash ₹11.2k · UPI ₹7.2k</div></div>
  <div class="stat danger"><div class="label">Outstanding / उधार</div><div class="value">₹86,050</div><div class="sub down">42 customers overdue</div></div>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h3>Today's Deliveries — Route wise</h3><span class="link">View all</span></div>
  <table><thead><tr><th>Customer</th><th>Route</th><th>Product</th><th>Status</th><th class="num">Amount</th></tr></thead>
  <tbody>
    <tr><td>Meena Sharma</td><td><span class="route-tag">Route 2</span></td><td>Milk 1L</td><td><span class="badge ok">Delivered</span></td><td class="num">₹56</td></tr>
    <tr><td>Rajeev Tomar</td><td><span class="route-tag">Route 2</span></td><td>Milk 2L, Curd</td><td><span class="badge ok">Delivered</span></td><td class="num">₹142</td></tr>
    <tr><td>Anita Gupta</td><td><span class="route-tag">Route 4</span></td><td>Milk 1L</td><td><span class="badge pending">Pending</span></td><td class="num">₹56</td></tr>
    <tr><td>Suresh Rawat</td><td><span class="route-tag">Route 1</span></td><td>Ghee 500g</td><td><span class="badge due">Skipped</span></td><td class="num">₹320</td></tr>
    <tr><td>Poonam Negi</td><td><span class="route-tag">Route 3</span></td><td>Milk 1.5L</td><td><span class="badge ok">Delivered</span></td><td class="num">₹84</td></tr>
  </tbody></table></div>
  <div class="panel"><div class="panel-head"><h3>Recent Ledger Activity</h3><span class="link">Open ledger</span></div>
  <div class="ledger-list">
    <div class="ledger-item"><div><div class="who">Anita Gupta</div><div class="when">Milk delivered · 07:40 AM</div></div><div class="amt credit">+₹56</div></div>
    <div class="ledger-item"><div><div class="who">Rajeev Tomar</div><div class="when">UPI payment received</div></div><div class="amt paid">−₹1,200</div></div>
    <div class="ledger-item"><div><div class="who">Suresh Rawat</div><div class="when">Delivery skipped · marked उधार</div></div><div class="amt credit">+₹320</div></div>
    <div class="ledger-item"><div><div class="who">Poonam Negi</div><div class="when">Cash collected by Ramesh</div></div><div class="amt paid">−₹500</div></div>
  </div></div>
</div>`;

D.customers = `
<div class="stat-row">
  <div class="stat"><div class="label">Total Customers</div><div class="value">1,240</div><div class="sub">on 8 routes</div></div>
  <div class="stat blue"><div class="label">Active</div><div class="value">1,190</div><div class="sub up">96%</div></div>
  <div class="stat butter"><div class="label">Paused</div><div class="value">38</div><div class="sub">temporary hold</div></div>
  <div class="stat danger"><div class="label">Overdue Balance</div><div class="value">42</div><div class="sub down">customers</div></div>
</div>
<div class="panel"><div class="panel-head"><h3>All Customers</h3><span class="link" onclick="loadDairy('customers-add')">+ Add Customer</span></div>
<table><thead><tr><th>Name</th><th>Route</th><th>Plan</th><th>Monthly Avg</th><th>Status</th><th class="num">Outstanding</th><th></th></tr></thead>
<tbody>
  <tr><td>Meena Sharma</td><td><span class="route-tag">Route 2</span></td><td>Milk 1L Daily</td><td>₹1,680</td><td><span class="badge ok">Active</span></td><td class="num">₹0</td><td><span class="link" onclick="loadDairy('customers-edit')">Edit</span></td></tr>
  <tr><td>Rajeev Tomar</td><td><span class="route-tag">Route 2</span></td><td>Milk 2L + Curd</td><td>₹4,260</td><td><span class="badge ok">Active</span></td><td class="num">₹0</td><td><span class="link" onclick="loadDairy('customers-edit')">Edit</span></td></tr>
  <tr><td>Anita Gupta</td><td><span class="route-tag">Route 4</span></td><td>Milk 1L Daily</td><td>₹1,680</td><td><span class="badge due">Overdue</span></td><td class="num">₹560</td><td><span class="link" onclick="loadDairy('customers-edit')">Edit</span></td></tr>
  <tr><td>Suresh Rawat</td><td><span class="route-tag">Route 1</span></td><td>Ghee Alternate</td><td>₹4,800</td><td><span class="badge due">Overdue</span></td><td class="num">₹960</td><td><span class="link" onclick="loadDairy('customers-edit')">Edit</span></td></tr>
  <tr><td>Poonam Negi</td><td><span class="route-tag">Route 3</span></td><td>Milk 1.5L Daily</td><td>₹2,520</td><td><span class="badge ok">Active</span></td><td class="num">₹240</td><td><span class="link" onclick="loadDairy('customers-edit')">Edit</span></td></tr>
  <tr><td>Dev Prakash</td><td><span class="route-tag">Route 4</span></td><td>Milk 1L + Paneer</td><td>₹3,100</td><td><span class="badge paused">Paused</span></td><td class="num">₹0</td><td><span class="link" onclick="loadDairy('customers-edit')">Edit</span></td></tr>
</tbody></table></div>`;

D.products = `
<div class="panel"><div class="panel-head"><h3>Products & Rates</h3><span class="link" onclick="loadDairy('products-add')">+ Add Product</span></div>
<table><thead><tr><th>Product</th><th>Unit</th><th class="num">Rate</th><th>Last Updated</th><th>Status</th><th></th></tr></thead>
<tbody>
  <tr><td>Full Cream Milk</td><td>1 Litre</td><td class="num">₹56</td><td>2 days ago</td><td><span class="badge ok">Active</span></td><td><span class="link">Edit</span></td></tr>
  <tr><td>Toned Milk</td><td>1 Litre</td><td class="num">₹48</td><td>2 days ago</td><td><span class="badge ok">Active</span></td><td><span class="link">Edit</span></td></tr>
  <tr><td>Curd</td><td>200 g</td><td class="num">₹35</td><td>1 week ago</td><td><span class="badge ok">Active</span></td><td><span class="link">Edit</span></td></tr>
  <tr><td>Paneer</td><td>250 g</td><td class="num">₹90</td><td>1 week ago</td><td><span class="badge ok">Active</span></td><td><span class="link">Edit</span></td></tr>
  <tr><td>Ghee</td><td>500 g</td><td class="num">₹320</td><td>3 weeks ago</td><td><span class="badge ok">Active</span></td><td><span class="link">Edit</span></td></tr>
  <tr><td>Buttermilk</td><td>500 ml</td><td class="num">₹25</td><td>3 weeks ago</td><td><span class="badge pending">Seasonal</span></td><td><span class="link">Edit</span></td></tr>
</tbody></table></div>`;

D.subscriptions = `
<div class="stat-row">
  <div class="stat"><div class="label">Active Subscriptions</div><div class="value">1,190</div><div class="sub up">96% of customers</div></div>
  <div class="stat blue"><div class="label">Daily Plans</div><div class="value">980</div><div class="sub">every day delivery</div></div>
  <div class="stat butter"><div class="label">Alternate-day Plans</div><div class="value">210</div><div class="sub">every other day</div></div>
  <div class="stat danger"><div class="label">Paused</div><div class="value">38</div><div class="sub down">customer requested</div></div>
</div>
<div class="panel"><div class="panel-head"><h3>Subscriptions</h3><span class="link" onclick="loadDairy('subscriptions-add')">+ New Subscription</span></div>
<table><thead><tr><th>Customer</th><th>Product</th><th class="num">Qty</th><th>Frequency</th><th>Status</th><th>Next Renewal</th></tr></thead>
<tbody>
  <tr><td>Meena Sharma</td><td>Full Cream Milk</td><td class="num">1L</td><td>Daily</td><td><span class="badge ok">Active</span></td><td>Auto-renews</td></tr>
  <tr><td>Rajeev Tomar</td><td>Milk + Curd</td><td class="num">2L + 200g</td><td>Daily</td><td><span class="badge ok">Active</span></td><td>Auto-renews</td></tr>
  <tr><td>Dev Prakash</td><td>Milk + Paneer</td><td class="num">1L + 250g</td><td>Alternate</td><td><span class="badge paused">Paused till 8 Sep</span></td><td>08 Sep 2026</td></tr>
  <tr><td>Kavita Rana</td><td>Full Cream Milk</td><td class="num">1L</td><td>Daily</td><td><span class="badge ok">Active</span></td><td>Auto-renews</td></tr>
</tbody></table></div>`;

D.routes = `
<div class="panel"><div class="panel-head"><h3>Delivery Routes</h3><span class="link" onclick="loadDairy('routes-add')">+ Add Route</span></div>
<div style="padding:15px;" class="cards-grid">
  ${[["Route 1","Ram Nagar, Kankhal",145,"Suresh Rawat",88],["Route 2","Jwalapur Main",210,"Amit Kumar",95],["Route 3","Shiv Colony, BHEL",180,"Ramesh Lal",92],["Route 4","Gurukul Marg",165,"Sanjay Bisht",74],["Route 5","Bhagwanpur",120,"Vinod Singh",81],["Route 6","Sarai, Roorkee Rd",140,"Manoj Thapa",90]]
  .map(r=>`<div class="info-card"><div class="h">${r[0]}</div><div class="s">${r[1]} · ${r[2]} stops · ${r[3]}</div><div class="progress"><div style="width:${r[4]}%"></div></div><div class="s" style="margin-bottom:0;">${r[4]}% delivered today</div></div>`).join('')}
</div></div>`;

D.deliveryboys = `
<div class="panel"><div class="panel-head"><h3>Delivery Boys</h3><span class="link" onclick="loadDairy('deliveryboys-add')">+ Add Delivery Boy</span></div>
<div style="padding:15px;" class="cards-grid">
  ${[["Ramesh Lal","Route 3","24/42","₹2,860","On duty"],["Suresh Rawat","Route 1","31/38","₹3,120","On duty"],["Amit Kumar","Route 2","40/44","₹4,010","On duty"],["Sanjay Bisht","Route 4","19/32","₹1,540","On duty"],["Vinod Singh","Route 5","0/28","₹0","Off duty"],["Manoj Thapa","Route 6","22/30","₹2,240","On duty"]]
  .map(b=>`<div class="info-card"><div style="display:flex;gap:10px;align-items:center;"><div class="avatar-lg">${b[0].split(' ').map(x=>x[0]).join('')}</div><div style="flex:1;"><div class="h">${b[0]}</div><div class="s" style="margin-bottom:0;">${b[1]}</div></div><span class="link" style="flex-shrink:0;" onclick="loadDairy('deliveryboys-edit')">Edit</span></div>
    <div style="display:flex;justify-content:space-between;margin-top:11px;font-size:12px;color:var(--ink-soft);"><span>Delivered ${b[2]}</span><span>Collected ${b[3]}</span></div>
    <div style="margin-top:9px;"><span class="badge ${b[4]==='On duty'?'ok':'paused'}">${b[4]}</span></div></div>`).join('')}
</div></div>`;

D.dailydeliveries = `
<div class="panel">
  <div class="panel-head"><h3>Daily Deliveries</h3><span class="link">Export Sheet</span></div>
  <div class="pills"><span class="pill active">All Routes</span><span class="pill">Route 1</span><span class="pill">Route 2</span><span class="pill">Route 3</span><span class="pill">Route 4</span></div>
  <table style="margin-top:6px;"><thead><tr><th>Customer</th><th>Route</th><th>Product</th><th>Delivery Boy</th><th>Status</th><th class="num">Amount</th></tr></thead>
  <tbody>
    <tr><td>Meena Sharma</td><td><span class="route-tag">Route 2</span></td><td>Milk 1L</td><td>Amit Kumar</td><td><span class="badge ok">Delivered</span></td><td class="num">₹56</td></tr>
    <tr><td>Rajeev Tomar</td><td><span class="route-tag">Route 2</span></td><td>Milk 2L, Curd</td><td>Amit Kumar</td><td><span class="badge ok">Delivered</span></td><td class="num">₹142</td></tr>
    <tr><td>Anita Gupta</td><td><span class="route-tag">Route 4</span></td><td>Milk 1L</td><td>Sanjay Bisht</td><td><span class="badge pending">Pending</span></td><td class="num">₹56</td></tr>
    <tr><td>Suresh Rawat</td><td><span class="route-tag">Route 1</span></td><td>Ghee 500g</td><td>Suresh Rawat</td><td><span class="badge due">Skipped</span></td><td class="num">₹320</td></tr>
    <tr><td>Poonam Negi</td><td><span class="route-tag">Route 3</span></td><td>Milk 1.5L</td><td>Ramesh Lal</td><td><span class="badge ok">Delivered</span></td><td class="num">₹84</td></tr>
    <tr><td>Kavita Rana</td><td><span class="route-tag">Route 3</span></td><td>Milk 1L</td><td>Ramesh Lal</td><td><span class="badge due">Skipped</span></td><td class="num">₹56</td></tr>
  </tbody></table></div>`;

D.otp = `
<div class="panel"><div class="panel-head"><h3>OTP Settings</h3></div>
  <div class="toggle-row"><div><div class="t-h">SMS OTP</div><div class="t-s">Send a 6-digit OTP by SMS on each delivery</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">WhatsApp OTP</div><div class="t-s">Fallback to WhatsApp if SMS fails</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">Skip OTP for prepaid customers</div><div class="t-s">Mark delivered without OTP for auto-pay plans</div></div><div class="switch"></div></div>
</div>
<div class="panel"><div class="panel-head"><h3>Recent Verifications</h3></div>
<table><thead><tr><th>Customer</th><th>Route</th><th>Sent</th><th>Verified At</th><th>Status</th></tr></thead>
<tbody>
  <tr><td>Poonam Negi</td><td><span class="route-tag">Route 3</span></td><td>07:38 AM</td><td>07:41 AM</td><td><span class="badge ok">Verified</span></td></tr>
  <tr><td>Meena Sharma</td><td><span class="route-tag">Route 2</span></td><td>07:20 AM</td><td>07:22 AM</td><td><span class="badge ok">Verified</span></td></tr>
  <tr><td>Kavita Rana</td><td><span class="route-tag">Route 3</span></td><td>07:45 AM</td><td>—</td><td><span class="badge due">Not verified</span></td></tr>
</tbody></table></div>`;

D.ledger = `
<div class="panel"><div class="panel-head"><h3>Customer Ledger — Anita Gupta</h3><span class="link">Change Customer</span></div>
<table><thead><tr><th>Date</th><th>Description</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance</th></tr></thead>
<tbody>
  <tr><td>01 Sep</td><td>Opening balance</td><td class="num">—</td><td class="num">—</td><td class="num">₹280</td></tr>
  <tr><td>02 Sep</td><td>Milk 1L delivered</td><td class="num">₹56</td><td class="num">—</td><td class="num">₹336</td></tr>
  <tr><td>03 Sep</td><td>Milk 1L delivered</td><td class="num">₹56</td><td class="num">—</td><td class="num">₹392</td></tr>
  <tr><td>04 Sep</td><td>Cash payment received</td><td class="num">—</td><td class="num">₹200</td><td class="num">₹192</td></tr>
  <tr><td>05 Sep</td><td>Milk 1L delivered</td><td class="num">₹56</td><td class="num">—</td><td class="num">₹248</td></tr>
</tbody></table></div>`;

D.credit = `
<div class="stat-row">
  <div class="stat danger"><div class="label">Total Outstanding</div><div class="value">₹86,050</div><div class="sub down">across all routes</div></div>
  <div class="stat"><div class="label">Customers with Credit</div><div class="value">42</div><div class="sub">of 1,240</div></div>
  <div class="stat blue"><div class="label">Avg Credit Age</div><div class="value">11 days</div><div class="sub">since last payment</div></div>
  <div class="stat butter"><div class="label">Limit Breaches</div><div class="value">6</div><div class="sub">over customer credit limit</div></div>
</div>
<div class="panel"><div class="panel-head"><h3>Credit / उधार</h3><span class="link">Send Reminders</span></div>
<table><thead><tr><th>Customer</th><th class="num">Credit Limit</th><th class="num">Balance</th><th>Days Overdue</th><th></th></tr></thead>
<tbody>
  <tr><td>Anita Gupta</td><td class="num">₹1,000</td><td class="num">₹560</td><td>6 days</td><td><span class="link">Remind</span></td></tr>
  <tr><td>Suresh Rawat</td><td class="num">₹800</td><td class="num">₹960</td><td><span style="color:var(--danger);font-weight:700;">14 days · over limit</span></td><td><span class="link">Remind</span></td></tr>
  <tr><td>Poonam Negi</td><td class="num">₹500</td><td class="num">₹240</td><td>3 days</td><td><span class="link">Remind</span></td></tr>
</tbody></table></div>`;

D.emi = `
<div class="panel"><div class="panel-head"><h3>EMI / Payment Plans</h3><span class="link">+ New Plan</span></div>
<table><thead><tr><th>Customer</th><th>Item</th><th class="num">Instalment</th><th>Progress</th><th>Next Due</th></tr></thead>
<tbody>
  <tr><td>Poonam Negi</td><td>Cooler Deposit</td><td class="num">₹500/mo</td><td style="min-width:140px;"><div class="progress"><div style="width:33%"></div></div>2 of 6</td><td>10 Sep 2026</td></tr>
  <tr><td>Dev Prakash</td><td>Cooler Deposit</td><td class="num">₹500/mo</td><td style="min-width:140px;"><div class="progress"><div style="width:66%"></div></div>4 of 6</td><td>12 Sep 2026</td></tr>
  <tr><td>Kavita Rana</td><td>Milk Can Advance</td><td class="num">₹300/mo</td><td style="min-width:140px;"><div class="progress"><div style="width:100%"></div></div>4 of 4</td><td>Completed</td></tr>
</tbody></table></div>`;

D.payments = `
<div class="stat-row">
  <div class="stat"><div class="label">Cash Today</div><div class="value">₹11,220</div><div class="sub">collected on-route</div></div>
  <div class="stat blue"><div class="label">UPI Today</div><div class="value">₹7,200</div><div class="sub">via delivery app</div></div>
  <div class="stat butter"><div class="label">This Month Total</div><div class="value">₹5.4L</div><div class="sub up">↑ 6% vs Aug</div></div>
  <div class="stat danger"><div class="label">Pending Reconciliation</div><div class="value">₹1,860</div><div class="sub down">3 delivery boys</div></div>
</div>
<div class="panel"><div class="panel-head"><h3>Payments</h3><span class="link">Reconcile Today</span></div>
<table><thead><tr><th>Date</th><th>Customer</th><th>Mode</th><th>Collected By</th><th>Status</th><th class="num">Amount</th></tr></thead>
<tbody>
  <tr><td>05 Sep, 07:41</td><td>Poonam Negi</td><td>Cash</td><td>Ramesh Lal</td><td><span class="badge ok">Reconciled</span></td><td class="num">₹84</td></tr>
  <tr><td>05 Sep, 07:22</td><td>Meena Sharma</td><td>UPI</td><td>Amit Kumar</td><td><span class="badge ok">Reconciled</span></td><td class="num">₹56</td></tr>
  <tr><td>05 Sep, 07:15</td><td>Rajeev Tomar</td><td>Cash</td><td>Amit Kumar</td><td><span class="badge pending">Pending</span></td><td class="num">₹1,200</td></tr>
</tbody></table></div>`;

D.billingm = `
<div class="panel"><div class="panel-head"><h3>Monthly Billing — September 2026</h3><span class="link">Generate All Bills</span></div>
<table><thead><tr><th>Customer</th><th>Route</th><th class="num">Amount</th><th>Status</th><th>Generated On</th></tr></thead>
<tbody>
  <tr><td>Meena Sharma</td><td><span class="route-tag">Route 2</span></td><td class="num">₹1,680</td><td><span class="badge ok">Paid</span></td><td>01 Sep 2026</td></tr>
  <tr><td>Rajeev Tomar</td><td><span class="route-tag">Route 2</span></td><td class="num">₹4,260</td><td><span class="badge pending">Pending</span></td><td>01 Sep 2026</td></tr>
  <tr><td>Anita Gupta</td><td><span class="route-tag">Route 4</span></td><td class="num">₹1,680</td><td><span class="badge due">Overdue</span></td><td>01 Sep 2026</td></tr>
  <tr><td>Poonam Negi</td><td><span class="route-tag">Route 3</span></td><td class="num">₹2,520</td><td><span class="badge pending">Pending</span></td><td>01 Sep 2026</td></tr>
</tbody></table></div>`;

D.inventory = `
<div class="panel"><div class="panel-head"><h3>Inventory</h3><span class="link">+ Stock Entry</span></div>
<table><thead><tr><th>Product</th><th class="num">Opening</th><th class="num">Sold Today</th><th class="num">Closing</th><th>Reorder Level</th></tr></thead>
<tbody>
  <tr><td>Full Cream Milk</td><td class="num">420 L</td><td class="num">312 L</td><td class="num">108 L</td><td><span class="badge ok">OK</span></td></tr>
  <tr><td>Toned Milk</td><td class="num">180 L</td><td class="num">140 L</td><td class="num">40 L</td><td><span class="badge pending">Reorder soon</span></td></tr>
  <tr><td>Curd</td><td class="num">60 kg</td><td class="num">18 kg</td><td class="num">42 kg</td><td><span class="badge ok">OK</span></td></tr>
  <tr><td>Ghee</td><td class="num">25 kg</td><td class="num">4 kg</td><td class="num">21 kg</td><td><span class="badge ok">OK</span></td></tr>
  <tr><td>Paneer</td><td class="num">15 kg</td><td class="num">13 kg</td><td class="num">2 kg</td><td><span class="badge due">Low stock</span></td></tr>
</tbody></table></div>`;

D.reports = `
<div class="stat-row">
  <div class="stat"><div class="label">Revenue This Month</div><div class="value">₹5.4L</div><div class="sub up">↑ 6% vs Aug</div></div>
  <div class="stat blue"><div class="label">Collection Efficiency</div><div class="value">94%</div><div class="sub">on-time payments</div></div>
  <div class="stat butter"><div class="label">Delivery Success Rate</div><div class="value">96%</div><div class="sub">this month</div></div>
  <div class="stat danger"><div class="label">Churn</div><div class="value">1.2%</div><div class="sub down">customers paused/cancelled</div></div>
</div>
<div class="panel"><div class="panel-head"><h3>Collection Efficiency by Route</h3></div>
<div>
  <div class="bar-row"><div class="bl">Route 1</div><div class="bar-track"><div class="bar-fill" style="width:88%"></div></div><div class="bv">88%</div></div>
  <div class="bar-row"><div class="bl">Route 2</div><div class="bar-track"><div class="bar-fill" style="width:95%"></div></div><div class="bv">95%</div></div>
  <div class="bar-row"><div class="bl">Route 3</div><div class="bar-track"><div class="bar-fill" style="width:92%"></div></div><div class="bv">92%</div></div>
  <div class="bar-row"><div class="bl">Route 4</div><div class="bar-track"><div class="bar-fill" style="width:74%"></div></div><div class="bv">74%</div></div>
</div></div>`;

D.whatsapp = `
<div class="panel"><div class="panel-head"><h3>Message Templates</h3><span class="link">+ New Template</span></div>
  <div class="toggle-row"><div><div class="t-h">Bill Ready</div><div class="t-s">Sent on the 1st of every month with the bill amount</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">Payment Reminder</div><div class="t-s">Sent 3 days before due date and on overdue</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">Delivery Confirmation</div><div class="t-s">Sent right after OTP verification</div></div><div class="switch"></div></div>
</div>
<div class="panel"><div class="panel-head"><h3>Recent Sends</h3></div>
<table><thead><tr><th>Customer</th><th>Template</th><th>Channel</th><th>Sent</th><th>Status</th></tr></thead>
<tbody>
  <tr><td>Anita Gupta</td><td>Payment Reminder</td><td>WhatsApp</td><td>Today, 09:00</td><td><span class="badge ok">Delivered</span></td></tr>
  <tr><td>Suresh Rawat</td><td>Payment Reminder</td><td>SMS</td><td>Today, 09:00</td><td><span class="badge ok">Delivered</span></td></tr>
  <tr><td>1,240 customers</td><td>Bill Ready</td><td>WhatsApp</td><td>01 Sep, 08:00</td><td><span class="badge ok">Sent</span></td></tr>
</tbody></table></div>`;

D.settings = `
<div class="panel"><div class="panel-head"><h3>Business Profile</h3></div>
<div class="form-grid">
  <div class="field"><label>Dairy Name</label><input value="Shree Krishna Dairy"></div>
  <div class="field"><label>Owner Name</label><input value="Rakesh Kumar"></div>
  <div class="field"><label>Phone Number</label><input value="+91 98765 43210"></div>
  <div class="field"><label>Plan</label><select><option>Premium</option></select></div>
  <div class="field full"><label>Address</label><textarea>Shop No. 4, Jwalapur Road, Haridwar, Uttarakhand</textarea></div>
</div></div>
<div class="panel"><div class="panel-head"><h3>Notification Preferences</h3></div>
  <div class="toggle-row"><div><div class="t-h">Low stock alerts</div><div class="t-s">Notify when inventory falls below reorder level</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">Daily collection summary</div><div class="t-s">SMS summary at 9 PM every day</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">Delivery boy no-show alert</div><div class="t-s">Notify if a route hasn't started by 8 AM</div></div><div class="switch"></div></div>
</div>`;

/* ---- Add / Edit form screens (Dairy Admin) ---- */
function formBack(label, targetKey, targetFn){
  return `<div style="margin-bottom:14px;"><button class="btn secondary sm" onclick="${targetFn}('${targetKey}')">← Back to ${label}</button></div>`;
}

D['customers-add'] = `
${formBack('Customers','customers','loadDairy')}
<div class="panel">
  <div class="panel-head"><h3>Personal Details</h3></div>
  <div class="form-grid">
    <div class="field"><label>Customer Name</label><div class="field-ic"><span class="ic">☺</span><input placeholder="e.g. Rakesh Sharma"></div></div>
    <div class="field"><label>Mobile Number (Optional)</label><div class="field-ic"><span class="ic">☎</span><input placeholder="+91 00000 00000"></div></div>
    <div class="field full"><label>Full Address</label><div class="field-ic"><span class="ic">📍</span><input placeholder="House no., street, area"></div></div>
    <div class="field"><label>Delivery Route</label><select><option>Route 1</option><option>Route 2</option><option selected>Route 3</option><option>Route 4</option></select></div>
  </div>
</div>
<div class="panel">
  <div class="panel-head"><h3>Supply Preference</h3></div>
  <div class="seg-group" style="padding:16px 19px;">
    <div class="seg-option active"><span class="ic">☀️</span>MORNING</div>
    <div class="seg-option"><span class="ic">🌙</span>EVENING</div>
    <div class="seg-option"><span class="ic">🌗</span>BOTH</div>
  </div>
</div>
<div class="panel">
  <div class="panel-head"><h3>Product Rates</h3></div>
  <div class="toggle-row"><div><div class="t-h">Full Cream Milk (L)</div><div class="t-s">₹56 per litre — standard rate</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">Toned Milk (L)</div><div class="t-s">₹48 per litre — standard rate</div></div><div class="switch"></div></div>
  <div class="toggle-row"><div><div class="t-h">Curd (200g)</div><div class="t-s">₹35 — standard rate</div></div><div class="switch"></div></div>
  <div class="toggle-row"><div><div class="t-h">Paneer (250g)</div><div class="t-s">₹90 — standard rate</div></div><div class="switch"></div></div>
</div>
<button class="btn pill" style="width:100%;padding:13px;" onclick="loadDairy('customers')">💾 Save Customer</button>
<div style="height:8px;"></div>`;

D['customers-edit'] = `
${formBack('Customers','customers','loadDairy')}
<div class="panel">
  <div class="panel-head"><h3>Personal Details — Anita Gupta</h3></div>
  <div class="form-grid">
    <div class="field"><label>Customer Name</label><div class="field-ic"><span class="ic">☺</span><input value="Anita Gupta"></div></div>
    <div class="field"><label>Mobile Number</label><div class="field-ic"><span class="ic">☎</span><input value="+91 98765 12340"></div></div>
    <div class="field full"><label>Full Address</label><div class="field-ic"><span class="ic">📍</span><input value="B-12, Shiv Colony, near Ram Mandir, Haridwar"></div></div>
    <div class="field"><label>Delivery Route</label><select><option>Route 1</option><option>Route 2</option><option>Route 3</option><option selected>Route 4</option></select></div>
  </div>
</div>
<div class="panel">
  <div class="panel-head"><h3>Supply Preference</h3></div>
  <div class="seg-group" style="padding:16px 19px;">
    <div class="seg-option active"><span class="ic">☀️</span>MORNING</div>
    <div class="seg-option"><span class="ic">🌙</span>EVENING</div>
    <div class="seg-option"><span class="ic">🌗</span>BOTH</div>
  </div>
</div>
<div class="panel">
  <div class="panel-head"><h3>Product Rates</h3></div>
  <div class="toggle-row"><div><div class="t-h">Full Cream Milk (L)</div><div class="t-s">₹56 per litre</div></div><div class="switch on"></div></div>
  <div class="toggle-row"><div><div class="t-h">Toned Milk (L)</div><div class="t-s">₹48 per litre</div></div><div class="switch"></div></div>
  <div class="toggle-row"><div><div class="t-h">Curd (200g)</div><div class="t-s">₹35</div></div><div class="switch on"></div></div>
</div>
<div style="display:flex;gap:10px;">
  <button class="btn pill" style="flex:1;padding:13px;" onclick="loadDairy('customers')">💾 Update Customer</button>
  <button class="btn secondary pill" style="padding:13px 20px;" onclick="loadDairy('customers')">Cancel</button>
</div>
<div style="height:8px;"></div>`;

D['products-add'] = `
${formBack('Products & Rates','products','loadDairy')}
<div class="panel">
  <div class="panel-head"><h3>Add New Product</h3></div>
  <div class="form-grid">
    <div class="field full"><label>Product Name</label><input placeholder="e.g. Cow Ghee"></div>
    <div class="field"><label>Unit</label><select><option>Litre (L)</option><option>Kilogram (kg)</option><option>Gram (g)</option><option>Piece</option></select></div>
    <div class="field"><label>Rate (₹)</label><input placeholder="e.g. 60" type="number"></div>
  </div>
  <div class="toggle-row"><div><div class="t-h">Active</div><div class="t-s">Available for customer selection</div></div><div class="switch on"></div></div>
</div>
<button class="btn pill" style="width:100%;padding:13px;" onclick="loadDairy('products')">💾 Save Product</button>
<div style="height:8px;"></div>`;

D['subscriptions-add'] = `
${formBack('Subscriptions','subscriptions','loadDairy')}
<div class="panel">
  <div class="panel-head"><h3>New Subscription</h3></div>
  <div class="form-grid">
    <div class="field full"><label>Customer</label><select><option>Meena Sharma — Route 2</option><option>Anita Gupta — Route 4</option><option>Poonam Negi — Route 3</option></select></div>
    <div class="field"><label>Product</label><select><option>Full Cream Milk</option><option>Toned Milk</option><option>Curd</option><option>Paneer</option></select></div>
    <div class="field"><label>Quantity</label><input placeholder="e.g. 1.5L" value="1L"></div>
    <div class="field"><label>Start Date</label><input type="date"></div>
  </div>
  <div style="padding:0 19px 18px;"><label style="font-size:11.5px;font-weight:600;color:var(--ink-soft);display:block;margin-bottom:8px;">Frequency</label>
    <div class="seg-group" style="padding:0;">
      <div class="seg-option active"><span class="ic">📅</span>DAILY</div>
      <div class="seg-option"><span class="ic">🔁</span>ALTERNATE</div>
      <div class="seg-option"><span class="ic">🗓️</span>WEEKLY</div>
    </div>
  </div>
</div>
<button class="btn pill" style="width:100%;padding:13px;" onclick="loadDairy('subscriptions')">💾 Save Subscription</button>
<div style="height:8px;"></div>`;

D['routes-add'] = `
${formBack('Delivery Routes','routes','loadDairy')}
<div class="panel">
  <div class="panel-head"><h3>Add New Route</h3></div>
  <div class="form-grid">
    <div class="field"><label>Route Name</label><input placeholder="e.g. Route 7"></div>
    <div class="field"><label>Area / Locality</label><input placeholder="e.g. Kankhal Main Road"></div>
    <div class="field"><label>Assigned Delivery Boy</label><select><option>Ramesh Lal</option><option>Suresh Rawat</option><option>Amit Kumar</option><option>Unassigned</option></select></div>
    <div class="field"><label>Expected Stops</label><input type="number" placeholder="e.g. 150"></div>
  </div>
</div>
<button class="btn pill" style="width:100%;padding:13px;" onclick="loadDairy('routes')">💾 Save Route</button>
<div style="height:8px;"></div>`;

D['deliveryboys-add'] = `
${formBack('Delivery Boys','deliveryboys','loadDairy')}
<div class="panel">
  <div class="panel-head"><h3>Onboard Delivery Boy</h3></div>
  <div class="form-grid">
    <div class="field"><label>Full Name</label><div class="field-ic"><span class="ic">☺</span><input placeholder="e.g. Vikas Kumar"></div></div>
    <div class="field"><label>Mobile Number</label><div class="field-ic"><span class="ic">☎</span><input placeholder="+91 00000 00000"></div></div>
    <div class="field"><label>Assigned Route</label><select><option>Route 1</option><option>Route 2</option><option>Route 5 (Unassigned)</option></select></div>
    <div class="field"><label>Bank Account (for payouts)</label><div class="field-ic"><span class="ic">🏦</span><input placeholder="Account number"></div></div>
  </div>
  <div style="padding:0 19px 18px;"><label style="font-size:11.5px;font-weight:600;color:var(--ink-soft);display:block;margin-bottom:8px;">Vehicle Type</label>
    <div class="seg-group" style="padding:0;">
      <div class="seg-option active"><span class="ic">🛵</span>BIKE</div>
      <div class="seg-option"><span class="ic">🚲</span>CYCLE</div>
      <div class="seg-option"><span class="ic">🚶</span>ON FOOT</div>
    </div>
  </div>
</div>
<button class="btn pill" style="width:100%;padding:13px;" onclick="loadDairy('deliveryboys')">💾 Save Delivery Boy</button>
<div style="height:8px;"></div>`;

D['deliveryboys-edit'] = `
${formBack('Delivery Boys','deliveryboys','loadDairy')}
<div class="panel">
  <div class="panel-head"><h3>Edit Delivery Boy — Ramesh Lal</h3></div>
  <div class="form-grid">
    <div class="field"><label>Full Name</label><div class="field-ic"><span class="ic">☺</span><input value="Ramesh Lal"></div></div>
    <div class="field"><label>Mobile Number</label><div class="field-ic"><span class="ic">☎</span><input value="+91 90123 45678"></div></div>
    <div class="field"><label>Assigned Route</label><select><option selected>Route 3</option><option>Route 1</option><option>Route 2</option></select></div>
    <div class="field"><label>Bank Account (for payouts)</label><div class="field-ic"><span class="ic">🏦</span><input value="HDFC •••• 4821"></div></div>
  </div>
  <div style="padding:0 19px 18px;"><label style="font-size:11.5px;font-weight:600;color:var(--ink-soft);display:block;margin-bottom:8px;">Vehicle Type</label>
    <div class="seg-group" style="padding:0;">
      <div class="seg-option active"><span class="ic">🛵</span>BIKE</div>
      <div class="seg-option"><span class="ic">🚲</span>CYCLE</div>
      <div class="seg-option"><span class="ic">🚶</span>ON FOOT</div>
    </div>
  </div>
</div>
<div style="display:flex;gap:10px;">
  <button class="btn pill" style="flex:1;padding:13px;" onclick="loadDairy('deliveryboys')">💾 Update Details</button>
  <button class="btn secondary pill" style="padding:13px 20px;" onclick="loadDairy('deliveryboys')">Cancel</button>
</div>
<div style="height:8px;"></div>`;

S['clients-add'] = `
${formBack('Clients','clients','loadSuper')}
<div class="panel">
  <div class="panel-head"><h3>Add New Client</h3></div>
  <div class="form-grid">
    <div class="field"><label>Business Name</label><div class="field-ic"><span class="ic">🏬</span><input placeholder="e.g. Sharma Dairy"></div></div>
    <div class="field"><label>Owner Name</label><div class="field-ic"><span class="ic">☺</span><input placeholder="e.g. Rakesh Sharma"></div></div>
    <div class="field"><label>Phone Number</label><div class="field-ic"><span class="ic">☎</span><input placeholder="+91 00000 00000"></div></div>
    <div class="field"><label>City</label><div class="field-ic"><span class="ic">📍</span><input placeholder="e.g. Haridwar, UK"></div></div>
  </div>
  <div style="padding:0 19px 18px;"><label style="font-size:11.5px;font-weight:600;color:var(--ink-soft);display:block;margin-bottom:8px;">Plan</label>
    <div class="seg-group" style="padding:0;">
      <div class="seg-option"><span class="ic">◧</span>BASIC</div>
      <div class="seg-option active"><span class="ic">◨</span>STANDARD</div>
      <div class="seg-option"><span class="ic">◆</span>PREMIUM</div>
    </div>
  </div>
</div>
<button class="btn pill" style="width:100%;padding:13px;" onclick="loadSuper('clients')">💾 Save Client</button>
<div style="height:8px;"></div>`;

S['clients-edit'] = `
${formBack('Clients','clients','loadSuper')}
<div class="panel">
  <div class="panel-head"><h3>Edit Client — Shree Krishna Dairy</h3></div>
  <div class="form-grid">
    <div class="field"><label>Business Name</label><div class="field-ic"><span class="ic">🏬</span><input value="Shree Krishna Dairy"></div></div>
    <div class="field"><label>Owner Name</label><div class="field-ic"><span class="ic">☺</span><input value="Rakesh Kumar"></div></div>
    <div class="field"><label>Phone Number</label><div class="field-ic"><span class="ic">☎</span><input value="+91 98765 43210"></div></div>
    <div class="field"><label>City</label><div class="field-ic"><span class="ic">📍</span><input value="Haridwar, UK"></div></div>
  </div>
  <div style="padding:0 19px 18px;"><label style="font-size:11.5px;font-weight:600;color:var(--ink-soft);display:block;margin-bottom:8px;">Plan</label>
    <div class="seg-group" style="padding:0;">
      <div class="seg-option"><span class="ic">◧</span>BASIC</div>
      <div class="seg-option"><span class="ic">◨</span>STANDARD</div>
      <div class="seg-option active"><span class="ic">◆</span>PREMIUM</div>
    </div>
  </div>
</div>
<div style="display:flex;gap:10px;">
  <button class="btn pill" style="flex:1;padding:13px;" onclick="loadSuper('clients')">💾 Update Client</button>
  <button class="btn secondary pill" style="padding:13px 20px;" onclick="loadSuper('clients')">Cancel</button>
</div>
<div style="height:8px;"></div>`;

function renderNav(container, groups, activeKey, onClick){
  container.innerHTML = groups.map(g => `
    ${g.group ? `<div class="nav-group-label">${g.group}</div>` : ''}
    ${g.items.map(it => `<button class="nav-item ${it.key===activeKey?'current':''}" data-key="${it.key}"><span class="ic">${it.ic}</span>${it.label}</button>`).join('')}
  `).join('');
  container.querySelectorAll('.nav-item').forEach(btn=>{
    btn.addEventListener('click', ()=>onClick(btn.dataset.key));
  });
}

let superActive = 'clients';
let dairyActive = 'dashboard';

const superNavKeys = ['clients','billing','databases','plans','usage','support','settings'];
const dairyNavKeys = ['dashboard','customers','products','subscriptions','routes','deliveryboys','dailydeliveries','otp','ledger','credit','emi','payments','billingm','inventory','reports','whatsapp','settings'];
const superFormMap = {clients:'clients-add'};
const dairyFormMap = {customers:'customers-add', products:'products-add', subscriptions:'subscriptions-add', routes:'routes-add', deliveryboys:'deliveryboys-add'};

function loadSuper(key){
  if(superNavKeys.includes(key)) superActive = key;
  document.getElementById('super-title').textContent = superTitles[key][0];
  document.getElementById('super-meta').textContent = superTitles[key][1];
  const actionBtn = document.getElementById('super-action');
  actionBtn.textContent = superTitles[key][2];
  actionBtn.style.display = superTitles[key][2] ? '' : 'none';
  actionBtn.onclick = superFormMap[key] ? (()=>loadSuper(superFormMap[key])) : null;
  document.getElementById('super-content').innerHTML = S[key] || '<p>Coming soon.</p>';
  renderNav(document.getElementById('nav-super'), superNav, superActive, loadSuper);
  closeDrawer('super');
  window.scrollTo({top:0});
}
function loadDairy(key){
  if(dairyNavKeys.includes(key)) dairyActive = key;
  document.getElementById('dairy-title').textContent = dairyTitles[key][0];
  document.getElementById('dairy-meta').textContent = dairyTitles[key][1];
  const actionBtn = document.getElementById('dairy-action');
  actionBtn.textContent = dairyTitles[key][2];
  actionBtn.style.display = dairyTitles[key][2] ? '' : 'none';
  actionBtn.onclick = dairyFormMap[key] ? (()=>loadDairy(dairyFormMap[key])) : null;
  document.getElementById('dairy-content').innerHTML = D[key] || '<p>Coming soon.</p>';
  renderNav(document.getElementById('nav-dairy'), dairyNav, dairyActive, loadDairy);
  closeDrawer('dairy');
  window.scrollTo({top:0});
}
function closeDrawer(which){
  document.getElementById('sidebar-'+which).classList.remove('open');
  document.querySelector(`.overlay[data-overlay="${which}"]`).classList.remove('show');
}
document.querySelectorAll('.menu-btn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const which = btn.dataset.drawer;
    document.getElementById('sidebar-'+which).classList.add('open');
    document.querySelector(`.overlay[data-overlay="${which}"]`).classList.add('show');
  });
});
document.querySelectorAll('.overlay').forEach(ov=>{
  ov.addEventListener('click', ()=>closeDrawer(ov.dataset.overlay));
});

/* Reliable responsive breakpoints via ResizeObserver (works regardless of
   CSS container-query support) — sidebar stays closed by default on mobile
   width and only opens via the hamburger button. */
function setupResponsiveShell(shellEl, which){
  if(!shellEl) return;
  const ro = new ResizeObserver(entries=>{
    const w = entries[0].contentRect.width;
    const narrow = w < 860;
    shellEl.classList.toggle('narrow', narrow);
    shellEl.classList.toggle('narrow-sm', w < 480);
    if(!narrow) closeDrawer(which);
  });
  ro.observe(shellEl);
}
setupResponsiveShell(document.querySelector('#view-superadmin .shell'), 'super');
setupResponsiveShell(document.querySelector('#view-clientadmin .shell'), 'dairy');

/* ============================================================
   DELIVERY BOY APP (phone, with real in-app navigation)
   ============================================================ */
const DT = {};

DT.deliveries = { topbar: `
  <div class="app-topbar">
    <div class="greet">नमस्ते Ramesh</div>
    <div class="h">Route 3 — 18 stops left</div>
    <span class="route-pill">Today · 05 Sep</span>
  </div>`, body: `
  <div class="summary-strip" style="margin-top:-2px;">
    <div class="box"><div class="v">42</div><div class="l">Total stops</div></div>
    <div class="box"><div class="v">24</div><div class="l">Delivered</div></div>
    <div class="box"><div class="v">₹2,860</div><div class="l">Collected</div></div>
  </div>
  <div class="stop-card">
    <div class="row1"><div><div class="cname">Poonam Negi</div><div class="caddr">B-12, Shiv Colony</div></div><span class="badge pending">Next</span></div>
    <div class="cprod">Milk 1.5L · Curd 200g</div>
    <div class="due-tag">Outstanding ₹240</div>
    <div class="actions"><button class="btn ghost">Unavailable</button><button class="btn" onclick="loadDelivery('otp')">Verify OTP</button></div>
  </div>
  <div class="stop-card">
    <div class="row1"><div><div class="cname">Dev Prakash</div><div class="caddr">14, Gurukul Marg</div></div><span class="badge ok">Delivered</span></div>
    <div class="cprod">Milk 1L, Paneer 250g</div>
    <div class="actions"><button class="btn secondary" style="flex:1;">Collect Cash</button><button class="btn secondary" style="flex:1;">UPI Receipt</button></div>
  </div>
  <div class="stop-card">
    <div class="row1"><div><div class="cname">Kavita Rana</div><div class="caddr">7, Ram Nagar</div></div><span class="badge due">Skipped</span></div>
    <div class="cprod">Milk 1L</div>
    <div class="due-tag">Marked उधार · ₹56 added</div>
  </div>
  <div style="height:8px;"></div>` };

DT.otp = { topbar: `
  <div class="app-topbar plain">
    <button class="back" onclick="loadDelivery('deliveries')">←</button>
    <div class="h">Confirm delivery</div>
  </div>`, body: `
  <div class="otp-card">
    <div class="ic-lg">✆</div>
    <div class="lead">OTP भेजा गया Poonam Negi के नंबर पर — डिलीवरी पूरी करने के लिए दर्ज करें</div>
    <div class="otp-digits"><input maxlength="1" value="4"><input maxlength="1" value="8"><input maxlength="1" value="1"><input maxlength="1"><input maxlength="1"><input maxlength="1"></div>
    <button class="btn" style="width:100%;" onclick="loadDelivery('deliveries')">Confirm Delivery</button>
    <div style="margin-top:11px;"><button class="btn ghost" style="width:100%;background:transparent;border:1px solid var(--line-strong);color:var(--ink-soft);" onclick="loadDelivery('deliveries')">Customer Unavailable</button></div>
  </div>
  <div class="section-label">Collect payment</div>
  <div style="padding:0 14px;display:flex;gap:7px;">
    <button class="btn secondary" style="flex:1;padding:11px 0;">💵 Cash ₹84</button>
    <button class="btn secondary" style="flex:1;padding:11px 0;">📲 UPI</button>
  </div>
  <div style="height:14px;"></div>` };

DT.route = { topbar: `
  <div class="app-topbar">
    <div class="greet">Route overview</div>
    <div class="h">Route 3 · Shiv Colony</div>
    <span class="route-pill">42 stops · 6.2 km</span>
  </div>`, body: `
  <div class="stop-card"><div class="row1"><div><div class="cname">1. Poonam Negi</div><div class="caddr">B-12, Shiv Colony</div></div><span class="badge pending">Next</span></div></div>
  <div class="stop-card"><div class="row1"><div><div class="cname">2. Dev Prakash</div><div class="caddr">14, Gurukul Marg</div></div><span class="badge ok">Done</span></div></div>
  <div class="stop-card"><div class="row1"><div><div class="cname">3. Kavita Rana</div><div class="caddr">7, Ram Nagar</div></div><span class="badge due">Skipped</span></div></div>
  <div class="stop-card"><div class="row1"><div><div class="cname">4. Meena Sharma</div><div class="caddr">22, Shiv Colony</div></div><span class="badge ok">Done</span></div></div>
  <div class="stop-card"><div class="row1"><div><div class="cname">5. Rajeev Tomar</div><div class="caddr">9, BHEL Colony</div></div><span class="badge ok">Done</span></div></div>
  <div style="height:8px;"></div>` };

DT.collections = { topbar: `
  <div class="app-topbar">
    <div class="greet">Today's collections</div>
    <div class="h">₹2,860 collected</div>
    <span class="route-pill">24 of 42 stops billed</span>
  </div>`, body: `
  <div class="summary-strip" style="margin-top:-2px;">
    <div class="box"><div class="v">₹1,640</div><div class="l">Cash</div></div>
    <div class="box"><div class="v">₹1,220</div><div class="l">UPI</div></div>
    <div class="box"><div class="v">₹376</div><div class="l">उधार added</div></div>
  </div>
  <div class="history-wrap">
    <div class="history-row"><div>Poonam Negi — Cash</div><div class="d">07:41 AM · ₹84</div></div>
    <div class="history-row"><div>Meena Sharma — UPI</div><div class="d">07:22 AM · ₹56</div></div>
    <div class="history-row"><div>Rajeev Tomar — Cash</div><div class="d">07:15 AM · ₹1,200</div></div>
    <div class="history-row"><div>Kavita Rana — marked उधार</div><div class="d">07:05 AM · ₹56</div></div>
  </div>
  <div style="height:14px;"></div>` };

DT.profile = { topbar: `
  <div class="app-topbar plain">
    <div class="h">Profile & Settings</div>
  </div>`, body: `
  <div class="app-form-card" style="margin:0 0 12px;padding:0;">
    <div class="profile-header"><div class="av">RL</div><div><div class="nm">Ramesh Lal</div><div class="ph">+91 90123 45678 · Route 3</div></div></div>
    <div class="settings-section-label">WORK</div>
    <div class="settings-row"><div class="bic blue">🛵</div><div class="txt"><div class="t">Vehicle Details</div><div class="s">Bike · UK07 AB 1234</div></div><div class="chev">›</div></div>
    <div class="settings-row"><div class="bic green">🏦</div><div class="txt"><div class="t">Bank Account for Payouts</div><div class="s">HDFC •••• 4821</div></div><div class="chev">›</div></div>
    <div class="settings-section-label">ACCOUNT</div>
    <div class="settings-row"><div class="bic purple">🔒</div><div class="txt"><div class="t">Change Password</div><div class="s">Update your password</div></div><div class="chev">›</div></div>
    <div class="settings-row"><div class="bic blue">🌐</div><div class="txt"><div class="t">Language</div><div class="s">English</div></div><div class="chev">›</div></div>
    <div class="settings-section-label">SUPPORT</div>
    <div class="settings-row" style="cursor:pointer;" onclick="loadDelivery('issue')"><div class="bic orange">⚑</div><div class="txt"><div class="t">Report an Issue</div><div class="s">Route, payment or app problem</div></div><div class="chev">›</div></div>
    <div class="settings-row"><div class="bic red">⎋</div><div class="txt"><div class="t">Logout</div><div class="s">Sign out of this device</div></div><div class="chev">›</div></div>
  </div>
  <div style="height:8px;"></div>` };

DT.issue = { topbar: `
  <div class="app-topbar plain">
    <button class="back" onclick="loadDelivery('profile')">←</button>
    <div class="h">Report an Issue</div>
  </div>`, body: `
  <div class="app-form-card">
    <div class="fl"><label>What's the issue about?</label></div>
    <div class="seg-group" style="padding:0 0 14px;">
      <div class="seg-option active"><span class="ic">🚧</span>Route</div>
      <div class="seg-option"><span class="ic">₹</span>Payment</div>
      <div class="seg-option"><span class="ic">📦</span>Product</div>
      <div class="seg-option"><span class="ic">⋯</span>Other</div>
    </div>
    <div class="fl"><label>Related stop (optional)</label><select><option>Poonam Negi — B-12, Shiv Colony</option><option>Kavita Rana — 7, Ram Nagar</option><option>Not stop-specific</option></select></div>
    <div class="fl"><label>Describe the issue</label><textarea placeholder="Gali me gaadi nahi ja pa rahi, customer ka address update chahiye…"></textarea></div>
    <button class="btn" style="width:100%;" onclick="loadDelivery('profile')">Submit Report</button>
  </div>
  <div style="height:14px;"></div>` };

let deliveryMainTab = 'deliveries';
const deliveryMainKeys = ['deliveries','route','collections','profile'];
function loadDelivery(key){
  document.getElementById('d-topbar').innerHTML = DT[key].topbar;
  document.getElementById('d-body').innerHTML = DT[key].body;
  if(deliveryMainKeys.includes(key)) deliveryMainTab = key;
  document.querySelectorAll('#d-tabbar .t').forEach(t=> t.classList.toggle('active', t.dataset.key===deliveryMainTab));
}

/* ============================================================
   CUSTOMER APP (phone, with real in-app navigation)
   ============================================================ */
const CT = {};

CT.home = { topbar: `
  <div class="app-topbar">
    <div class="greet">नमस्ते Anita</div>
    <div class="h">Shree Krishna Dairy</div>
    <span class="route-pill">Route 3 · Delivered by Ramesh</span>
  </div>`, body: `
  <div class="summary-strip" style="margin-top:-2px;">
    <div class="box"><div class="v">28</div><div class="l">Deliveries</div></div>
    <div class="box"><div class="v">42L</div><div class="l">Milk this month</div></div>
    <div class="box"><div class="v">Active</div><div class="l">Subscription</div></div>
  </div>
  <div class="section-label">Recent deliveries</div>
  <div class="history-wrap">
    <div class="history-row"><div>04 Sep — Milk 1.5L, Curd 200g</div><div class="d">₹84</div></div>
    <div class="history-row"><div>03 Sep — Milk 1.5L</div><div class="d">₹63</div></div>
    <div class="history-row"><div>02 Sep — Skipped (Unavailable)</div><div class="d">—</div></div>
    <div class="history-row"><div>01 Sep — Milk 1.5L, Paneer 250g</div><div class="d">₹214</div></div>
  </div>
  <div style="height:14px;"></div>` };

CT.bill = { topbar: ``, body: `
  <div class="bill-hero" style="margin:-0px 0 14px;border-radius:0 0 22px 22px;">
    <div class="l">September बिल · Shree Krishna Dairy</div>
    <div class="amt">₹1,684</div>
    <div class="due">Due by 10 Sep · Outstanding ₹240 carried forward</div>
    <button class="btn">Pay Now</button>
  </div>
  <div class="section-label">Payment history</div>
  <div class="history-wrap">
    <div class="history-row"><div>28 Aug — UPI payment</div><div class="d">₹1,200</div></div>
    <div class="history-row"><div>15 Aug — Cash to Ramesh</div><div class="d">₹500</div></div>
    <div class="history-row"><div>01 Aug — August bill paid</div><div class="d">₹1,540</div></div>
  </div>
  <div style="height:14px;"></div>` };

CT.subscription = { topbar: `
  <div class="app-topbar">
    <div class="greet">Subscription</div>
    <div class="h">Daily Milk Plan — 1.5L</div>
    <span class="route-pill">Delivered by Ramesh · Route 3</span>
  </div>`, body: `
  <div class="stop-card">
    <div class="row1"><div><div class="cname">Pause delivery</div><div class="caddr">Skip upcoming days when you're away</div></div></div>
    <div class="actions"><button class="btn secondary" style="flex:1;">Pause 3 days</button><button class="btn" style="flex:1;">Resume</button></div>
  </div>
  <div class="stop-card">
    <div class="row1"><div><div class="cname">Outstanding balance</div><div class="caddr">Last payment 28 Aug · ₹1,200 via UPI</div></div><span class="badge due">₹240</span></div>
    <div class="actions"><button class="btn" style="flex:1;">Pay ₹240 Now</button></div>
  </div>
  <div class="stop-card">
    <div class="row1"><div><div class="cname">EMI — Cooler Deposit</div><div class="caddr">2 of 6 installments paid</div></div><span class="badge pending">₹500/mo</span></div>
  </div>
  <div style="height:8px;"></div>` };

CT.profile = { topbar: `
  <div class="app-topbar plain">
    <div class="h">Profile & Settings</div>
  </div>`, body: `
  <div class="app-form-card" style="margin:0 0 12px;padding:0;">
    <div class="profile-header"><div class="av">AG</div><div><div class="nm">Anita Gupta</div><div class="ph">+91 98765 12340</div></div></div>
    <div class="settings-section-label">ACCOUNT</div>
    <div class="settings-row" style="cursor:pointer;" onclick="loadCustomer('address')"><div class="bic blue">📍</div><div class="txt"><div class="t">Manage Address</div><div class="s">B-12, Shiv Colony, Haridwar</div></div><div class="chev">›</div></div>
    <div class="settings-row"><div class="bic green">💳</div><div class="txt"><div class="t">Payment Methods</div><div class="s">UPI, Cash on delivery</div></div><div class="chev">›</div></div>
    <div class="settings-row"><div class="bic purple">🔒</div><div class="txt"><div class="t">Change Password</div><div class="s">Update your password</div></div><div class="chev">›</div></div>
    <div class="settings-row"><div class="bic blue">🌐</div><div class="txt"><div class="t">Language</div><div class="s">English</div></div><div class="chev">›</div></div>
    <div class="settings-section-label">OTHERS</div>
    <div class="settings-row"><div class="bic orange">🎁</div><div class="txt"><div class="t">Refer & Earn</div><div class="s">Invite friends, get credit</div></div><div class="chev">›</div></div>
    <div class="settings-row"><div class="bic red">⎋</div><div class="txt"><div class="t">Logout</div><div class="s">Sign out of this device</div></div><div class="chev">›</div></div>
  </div>
  <div style="height:8px;"></div>` };

CT.address = { topbar: `
  <div class="app-topbar plain">
    <button class="back" onclick="loadCustomer('profile')">←</button>
    <div class="h">Manage Address</div>
  </div>`, body: `
  <div class="app-form-card">
    <div class="fl"><label>Full Name</label><input value="Anita Gupta"></div>
    <div class="fl"><label>Mobile Number</label><input value="+91 98765 12340"></div>
    <div class="fl"><label>Full Address</label><textarea>B-12, Shiv Colony, near Ram Mandir, Haridwar, Uttarakhand</textarea></div>
    <div class="fl"><label>Delivery preference</label></div>
    <div class="seg-group" style="padding:0 0 14px;">
      <div class="seg-option active"><span class="ic">☀️</span>Morning</div>
      <div class="seg-option"><span class="ic">🌙</span>Evening</div>
      <div class="seg-option"><span class="ic">🌗</span>Both</div>
    </div>
    <button class="btn" style="width:100%;" onclick="loadCustomer('profile')">Save Address</button>
  </div>
  <div style="height:14px;"></div>` };

let customerMainTab = 'home';
const customerMainKeys = ['home','bill','subscription','profile'];
function loadCustomer(key){
  document.getElementById('c-topbar').innerHTML = CT[key].topbar;
  document.getElementById('c-body').innerHTML = CT[key].body;
  if(customerMainKeys.includes(key)) customerMainTab = key;
  document.querySelectorAll('#c-tabbar .t').forEach(t=> t.classList.toggle('active', t.dataset.key===customerMainTab));
}

/* ============================================================
   TOP SWITCHER
   ============================================================ */
const mainTabs = document.getElementById('mainTabs');
const deviceToggle = document.getElementById('deviceToggle');
const deviceFrame = document.getElementById('deviceFrame');
const views = document.querySelectorAll('.view');

mainTabs.addEventListener('click', (e)=>{
  const btn = e.target.closest('button'); if(!btn) return;
  mainTabs.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  const target = btn.dataset.view;
  views.forEach(v=>v.classList.remove('active'));
  document.getElementById('view-'+target).classList.add('active');
});

deviceToggle.addEventListener('click', (e)=>{
  const btn = e.target.closest('button'); if(!btn) return;
  deviceToggle.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  deviceFrame.classList.toggle('mobile', btn.dataset.mode === 'mobile');
});

/* ---------------- INIT ---------------- */
loadSuper('clients');
loadDairy('dashboard');
loadDelivery('deliveries');
loadCustomer('home');