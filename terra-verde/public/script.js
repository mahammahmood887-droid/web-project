(function(){
  const form = document.querySelector('.auth-form');
  if(!form) return;
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const setErr = (input,msg) => {
    const f = input.closest('.field'); f.classList.toggle('invalid', !!msg);
    f.querySelector('.err').textContent = msg || '';
    return !msg;
  };
  const rules = {
    full_name: v => v.trim().length < 2 ? 'Please enter your name.' : '',
    email: v => emailRe.test(v.trim()) ? '' : "That email doesn't look quite right.",
    password: v => (form.dataset.mode === 'register' && v.length < 8) ? 'Use at least 8 characters.' : (!v ? 'Enter your password.' : ''),
    confirm: v => v !== form.password.value ? "Passwords don't match." : ''
  };
  form.querySelectorAll('input').forEach(i => {
    if(rules[i.name]) i.addEventListener('blur', () => setErr(i, rules[i.name](i.value)));
  });
  form.addEventListener('submit', e => {
    let ok = true;
    form.querySelectorAll('input').forEach(i => { if(rules[i.name] && !setErr(i, rules[i.name](i.value))) ok = false; });
    if(!ok) e.preventDefault();
  });
  document.querySelectorAll('.pw-toggle').forEach(b => b.addEventListener('click', () => {
    const inp = b.parentElement.querySelector('input');
    const show = inp.type === 'password'; inp.type = show ? 'text' : 'password';
    b.textContent = show ? 'Hide' : 'Show';
  }));
  const bar = document.querySelector('.strength i');
  if(bar && form.password) form.password.addEventListener('input', () => {
    const v = form.password.value;
    const score = [v.length>=8, /[A-Z]/.test(v), /\d/.test(v), /[^A-Za-z0-9]/.test(v)].filter(Boolean).length;
    bar.style.width = (score*25)+'%';
  });
})();


(function(){
  const KEY = 'tv_cart', MAX = 20;
  const read = () => {
    try {
      const c = JSON.parse(localStorage.getItem(KEY) || '[]');
      return (Array.isArray(c) ? c : []).map(i => ({
        id: String(i.id), name: String(i.name || ''), price: Number(i.price) || 0,
        quantity: Math.max(1, Math.min(MAX, parseInt(i.quantity, 10) || 1))
      })).filter(i => i.id && i.name);
    } catch (e) { return []; }
  };
  const paint = () => {
    const n = read().reduce((s, i) => s + i.quantity, 0);
    document.querySelectorAll('.bag-n').forEach(el => el.textContent = n);
  };
  const write = c => { try { localStorage.setItem(KEY, JSON.stringify(c)); } catch (e) {} paint(); };
  const add = (id, qty) => {
    const list = (window.TV && Array.isArray(TV.products)) ? TV.products : [];
    const p = list.find(x => String(x.id) === String(id)); if (!p) return false;
    const cart = read(), it = cart.find(i => i.id === String(p.id));
    if (it) it.quantity = Math.min(MAX, it.quantity + qty);
    else cart.push({ id: String(p.id), name: p.name, price: Number(p.price), quantity: Math.min(MAX, qty) });
    write(cart); return true;
  };
  window.TVBag = { read, write, add, MAX };

  document.addEventListener('click', e => {
    const btn = e.target.closest('.add-btn');
    if (btn) {
      if (window.TV && TV.loggedIn === false) { window.location.href = 'login.php'; return; }
      if (!add(btn.dataset.id, 1)) return;
      btn.textContent = '\u2713'; btn.classList.add('added');
      setTimeout(() => { btn.textContent = '+'; btn.classList.remove('added'); }, 1400);
      return;
    }
    if (e.target.closest('a[href="logout.php"]')) { try { ['tv_cart','tv_wish','tv_orders'].forEach(k => localStorage.removeItem(k)); } catch (err) {} }
  });
  window.addEventListener('storage', e => { if (e.key === KEY) paint(); });
  paint();
})();

(function(){
  if(!document.getElementById('productGrid')) return;

  const products = ((window.TV && TV.products) || []).map(p => ({
    id:p.id, name:p.name, concern:p.concern, concernLabel:p.concern_label,
    price:'$' + Number(p.price), bg:p.card_color, icon:p.icon_svg
  }));

  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const grid = document.getElementById('productGrid');
  function renderProducts(){
    grid.innerHTML = products.map(p => `
      <div class="product-card" data-concern="${esc(p.concern)}" style="--card-bg:${esc(p.bg)}">
        <svg class="icon" viewBox="0 0 52 52">${p.icon}</svg>
        <h3>${esc(p.name)}</h3>
        <p class="concern">${esc(p.concernLabel)}</p>
        <div class="price-row">
          <span class="price">${esc(p.price)}</span>
          <button class="add-btn" data-id="${esc(p.id)}" aria-label="Add ${esc(p.name)} to bag">+</button>
        </div>
      </div>
    `).join('');
  }
  renderProducts();

  const filterBar = document.getElementById('filterBar');
  filterBar.addEventListener('click', e => {
    const btn = e.target.closest('.filter-btn');
    if(!btn) return;
    filterBar.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const val = btn.dataset.filter;
    document.querySelectorAll('.product-card').forEach(card => {
      card.classList.toggle('hide', val !== 'all' && card.dataset.concern !== val);
    });
  });

  const quizData = [
    { q: "How does your skin usually feel by midday?",
      opts: [
        {label:"Shiny or prone to breakouts", tag:"clarify"},
        {label:"Tight, dry, or flaky", tag:"nourish"},
        {label:"Fine, but a little dull", tag:"glow"},
        {label:"Easily irritated or blotchy", tag:"calm"},
      ]},
    { q: "What's the one thing you wish your routine did better?",
      opts: [
        {label:"Keep oil in check without stripping", tag:"clarify"},
        {label:"Actually hold onto moisture", tag:"nourish"},
        {label:"Bring back some brightness", tag:"glow"},
        {label:"Stop reacting to everything", tag:"calm"},
      ]},
    { q: "Pick a texture you'd reach for on a bad skin day.",
      opts: [
        {label:"Something light and mattifying", tag:"clarify"},
        {label:"A thick, cushiony cream", tag:"nourish"},
        {label:"A silky, fast-absorbing serum", tag:"glow"},
        {label:"A soft, fragrance-free balm", tag:"calm"},
      ]},
  ];
  const results = {
    clarify: { product:"Eucalyptus Clarifying Mist", note:"A cooling, oil-balancing mist that clears congestion without over-drying — spritz after cleansing, before anything else." },
    nourish: { product:"Ivory Oat Cream", note:"A colloidal-oat cream built to rebuild your barrier. Layer it over serum at night and let it sit until morning." },
    glow: { product:"Peach Nectar Glow Serum", note:"A vitamin-C forward serum that evens tone over about three weeks of nightly use. Patch test first." },
    calm: { product:"Sage Calm Balm", note:"A five-ingredient balm made for reactive skin — no essential oils, no fragrance, just what your barrier needs." },
  };

  let step = 0;
  const answers = [];
  const quizCard = document.getElementById('quizCard');
  const stepTrack = document.getElementById('stepTrack');

  function updateTrack(){
    stepTrack.querySelectorAll('i').forEach((el, i) => {
      el.style.width = i < step ? '100%' : (i === step ? '50%' : '0%');
    });
  }

  function renderQuiz(){
    if(step >= quizData.length){
      const counts = {};
      answers.forEach(a => counts[a] = (counts[a]||0)+1);
      const winner = Object.keys(counts).sort((a,b) => counts[b]-counts[a])[0];
      const r = results[winner];
      quizCard.innerHTML = `
        <div class="quiz-result">
          <span class="result-tag">Your ritual match</span>
          <h3>${r.product}</h3>
          <p>${r.note}</p>
          <button class="restart-btn" id="restartQuiz">Start over</button>
        </div>
      `;
      document.getElementById('restartQuiz').onclick = () => { step = 0; answers.length = 0; updateTrack(); renderQuiz(); };
      stepTrack.querySelectorAll('i').forEach(el => el.style.width = '100%');
      return;
    }
    const current = quizData[step];
    quizCard.innerHTML = `
      <p class="quiz-question">${current.q}</p>
      <div class="quiz-options">
        ${current.opts.map(o => `<button class="quiz-option" data-tag="${o.tag}">${o.label}</button>`).join('')}
      </div>
      <div class="quiz-nav">
        <button class="quiz-back" id="quizBack" ${step===0?'disabled':''}>← Back</button>
        <span style="color:var(--ink-soft);font-size:0.85rem;">Step ${step+1} of ${quizData.length}</span>
      </div>
    `;
    quizCard.querySelectorAll('.quiz-option').forEach(btn => {
      btn.onclick = () => { answers[step] = btn.dataset.tag; step++; updateTrack(); renderQuiz(); };
    });
    const backBtn = document.getElementById('quizBack');
    backBtn.onclick = () => { if(step>0){ step--; updateTrack(); renderQuiz(); } };
    updateTrack();
  }
  renderQuiz();

  const slidesWrap = document.getElementById('tSlides');
  const slides = slidesWrap.children;
  const dotsWrap = document.getElementById('tDots');
  let tIndex = 0;
  let tTimer;

  for(let i=0;i<slides.length;i++){
    const d = document.createElement('button');
    if(i===0) d.classList.add('active');
    d.addEventListener('click', () => goTo(i));
    dotsWrap.appendChild(d);
  }

  function goTo(i){
    tIndex = (i + slides.length) % slides.length;
    slidesWrap.style.transform = `translateX(-${tIndex*100}%)`;
    [...dotsWrap.children].forEach((d,idx) => d.classList.toggle('active', idx===tIndex));
  }
  function autoAdvance(){ tTimer = setInterval(() => goTo(tIndex+1), 6000); }
  autoAdvance();

  document.getElementById('tPrev').addEventListener('click', () => { goTo(tIndex-1); clearInterval(tTimer); autoAdvance(); });
  document.getElementById('tNext').addEventListener('click', () => { goTo(tIndex+1); clearInterval(tTimer); autoAdvance(); });
  document.querySelector('.testimonial-wrap').addEventListener('mouseenter', () => clearInterval(tTimer));
  document.querySelector('.testimonial-wrap').addEventListener('mouseleave', autoAdvance);

  let startX = null;
  slidesWrap.addEventListener('touchstart', e => startX = e.touches[0].clientX);
  slidesWrap.addEventListener('touchend', e => {
    if(startX===null) return;
    const diff = e.changedTouches[0].clientX - startX;
    if(diff > 40) goTo(tIndex-1);
    if(diff < -40) goTo(tIndex+1);
    startX = null;
  });

  const faqData = [
    {q:"Are your products actually preservative-free?", a:"No — and we're wary of anyone who claims theirs are. We use a low percentage of a broad-spectrum preservative in anything water-based, listed plainly on the label, so your jar doesn't grow something unwanted in the shower."},
    {q:"How long does a batch stay fresh once opened?", a:"About four months for water-based formulas like the mist and serum, up to a year for the balm and bar. Each label is stamped with the pour date, not just an expiry guess."},
    {q:"Can I return something that didn't work for my skin?", a:"Yes, within 30 days, used or not. Email us a line about what happened — it helps us adjust the next batch, and we'll refund or swap the product either way."},
    {q:"Where do the ingredients actually come from?", a:"Eucalyptus and oat from a farm in Sandy, Oregon; clay from a family quarry in Bend; everything else from our own half-acre garden behind the studio."},
  ];
  const faqList = document.getElementById('faqList');
  faqList.innerHTML = faqData.map((f,i) => `
    <div class="faq-item">
      <button class="faq-q" data-i="${i}">${f.q}<span class="plus"></span></button>
      <div class="faq-a"><p>${f.a}</p></div>
    </div>
  `).join('');

  faqList.querySelectorAll('.faq-q').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      const ans = item.querySelector('.faq-a');
      const isOpen = item.classList.contains('open');
      faqList.querySelectorAll('.faq-item').forEach(i => {
        i.classList.remove('open');
        i.querySelector('.faq-a').style.maxHeight = null;
      });
      if(!isOpen){
        item.classList.add('open');
        ans.style.maxHeight = ans.scrollHeight + 'px';
      }
    });
  });

  const form = document.getElementById('newsletterForm');
  const emailInput = document.getElementById('newsletterEmail');
  const formMsg = document.getElementById('formMsg');
  const formBtn = form.querySelector('button');
  const say = (text, color) => { formMsg.textContent = text; formMsg.style.color = color; };
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const val = emailInput.value.trim();
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)){ say("That email doesn't look quite right.", '#7a2e17'); emailInput.focus(); return; }
    formBtn.disabled = true;
    try{
      const r = await fetch('index.php', {method:'POST', body:new URLSearchParams({action:'subscribe', email:val, csrf:TV.csrf})});
      if(!r.ok) throw new Error('failed');
      say("You're on the list \u2014 first report goes out Monday.", '#3a2a1a');
      formBtn.textContent = 'Added \u2713'; emailInput.value = '';
      setTimeout(() => { formBtn.textContent = 'Notify me'; formMsg.textContent = ''; }, 4000);
    }catch(err){
      say('Something went wrong. Please try again.', '#7a2e17');
    }finally{ formBtn.disabled = false; }
  });

  document.querySelectorAll('[data-scroll]').forEach(b =>
    b.addEventListener('click', () => document.getElementById(b.dataset.scroll).scrollIntoView()));

  const hamburgerBtn = document.getElementById('hamburgerBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  hamburgerBtn.addEventListener('click', () => {
    const open = mobileMenu.classList.toggle('open');
    hamburgerBtn.setAttribute('aria-expanded', open);
  });
  mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => mobileMenu.classList.remove('open')));

  const navLinks = document.querySelectorAll('[data-nav]');
  const sections = ['products','ritual','stories','faq'].map(id => document.getElementById(id));
  const backToTop = document.getElementById('backToTop');

  window.addEventListener('scroll', () => {
    const y = window.scrollY + 140;
    let current = null;
    sections.forEach(s => { if(s && y >= s.offsetTop) current = s.id; });
    navLinks.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#'+current));
    backToTop.classList.toggle('show', window.scrollY > 700);
  });
  backToTop.addEventListener('click', () => window.scrollTo({top:0, behavior:'smooth'}));

})();

(function(){
  const WKEY = 'tv_wish', OKEY = 'tv_orders';
  const $ = (s, r = document) => r.querySelector(s);
  const el = (tag, cls, text) => { const n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; };
  const money = n => '$' + Number(n).toFixed(2);
  const load = k => { try { const v = JSON.parse(localStorage.getItem(k) || '[]'); return Array.isArray(v) ? v : []; } catch (e) { return []; } };
  const save = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} };
  const products = () => (window.TV && Array.isArray(TV.products)) ? TV.products : [];
  const find = id => products().find(p => String(p.id) === String(id));

  const Wish = {
    read: () => load(WKEY).map(String),
    has: id => Wish.read().includes(String(id)),
    toggle(id){
      id = String(id); const w = Wish.read(), i = w.indexOf(id);
      if (i > -1) w.splice(i, 1); else w.push(id);
      save(WKEY, w); document.dispatchEvent(new CustomEvent('tv:wish'));
      return i === -1;
    }
  };

  const Orders = {
    read: () => load(OKEY).filter(o => o && Array.isArray(o.items)),
    add(o){
      const list = Orders.read();
      list.unshift({ t: Date.now(), total: Number(o.total) || 0,
        items: o.items.map(i => ({ id: String(i.id), name: String(i.name), price: Number(i.price), quantity: Number(i.quantity) })) });
      save(OKEY, list.slice(0, 5));
    }
  };

  function shipText(){
    const names = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'], now = new Date();
    for (let d = 0; d < 7; d++){
      const g = (now.getDay() + d) % 7;
      if (g === 2 || g === 5) return d === 0 ? 'Next shipment: today (' + names[g] + ')'
        : 'Next shipment: ' + names[g] + ' \u00B7 ' + (d === 1 ? 'tomorrow' : 'in ' + d + ' days');
    }
    return '';
  }
    window.TVOrders = Orders; window.TVExtras = { shipText, money, el };

  const INFO = {
    'Eucalyptus Clarifying Mist':   { ing:'Eucalyptus hydrosol, witch hazel, green tea, glycerin', use:'Close your eyes and spritz 2\u20133 times after cleansing, morning or midday.', size:'100 ml', pair:'Ivory Oat Cream' },
    'Peach Nectar Glow Serum':      { ing:'Peach nectar, vitamin C, hyaluronic acid, rose water', use:'Press 3\u20134 drops into clean skin at night. Patch test first.', size:'30 ml', pair:'Ivory Oat Cream' },
    'Ivory Oat Cream':              { ing:'Colloidal oat, shea butter, squalane, calendula extract', use:'Smooth over serum at night and let it sit until morning.', size:'50 ml', pair:'Peach Nectar Glow Serum' },
    'Pistachio Renewal Mask':       { ing:'Pistachio oil, green clay, aloe, honey', use:'Apply a thin layer, wait 10 minutes, then rinse with warm water. Twice a week.', size:'75 ml', pair:'Chamomile Dew Toner' },
    'Clay Detox Bar':               { ing:'Bend red clay, olive oil, tea tree, oat flour', use:'Lather in wet hands and massage on for 30 seconds. Rinse well.', size:'90 g bar', pair:'Sage Calm Balm' },
    'Sage Calm Balm':               { ing:'Sage, beeswax, jojoba, oat oil. No fragrance, no essential oils.', use:'Warm a pea-sized amount between fingers and press onto reactive areas.', size:'30 ml', pair:'Calendula Soothing Cleanser' },
    'Rosehip Midnight Oil':         { ing:'Rosehip seed oil, sea buckthorn, vitamin E', use:'Warm 3 drops between palms and press in as the last step at night.', size:'30 ml', pair:'Ivory Oat Cream' },
    'Calendula Soothing Cleanser':  { ing:'Calendula, chamomile, coconut-derived cleansers, glycerin', use:'Massage onto damp skin, then rinse with lukewarm water.', size:'120 ml', pair:'Chamomile Dew Toner' },
    'Chamomile Dew Toner':          { ing:'Chamomile hydrosol, aloe, panthenol, glycerin', use:'Pat into skin right after cleansing while it is still damp.', size:'120 ml', pair:'Sage Calm Balm' },
    'Rose Clay Polish':             { ing:'Rose clay, jojoba beads, rice powder, rosehip', use:'Mix with water, massage in small circles once a week, rinse.', size:'60 g', pair:'Eucalyptus Clarifying Mist' },
    'Elderflower Bright Eye Balm':  { ing:'Elderflower, cucumber extract, caffeine, shea', use:'Dab a tiny amount around the orbital bone with your ring finger.', size:'15 ml', pair:'Peach Nectar Glow Serum' },
    'Turmeric Radiance Mask':       { ing:'Turmeric, kaolin clay, yogurt powder, honey', use:'Apply a thin layer, leave 8 minutes, rinse with warm water. Once a week.', size:'75 ml', pair:'Peach Nectar Glow Serum' },
    'Lavender Night Balm':          { ing:'Lavender, beeswax, shea butter, jojoba', use:'Warm a small amount and press onto face and neck before bed.', size:'30 ml', pair:'Chamomile Dew Toner' },
    'Oat & Honey Hand Cream':       { ing:'Colloidal oat, raw honey, shea butter, almond oil', use:'Massage into hands and cuticles whenever they feel dry.', size:'60 ml', pair:'Beeswax & Honey Lip Salve' },
    'Beeswax & Honey Lip Salve':    { ing:'Beeswax, raw honey, sweet almond oil', use:'Swipe on whenever lips feel dry, especially before bed.', size:'10 g', pair:'Ivory Oat Cream' }
  };
  const infoFor = p => INFO[p.name] || { ing:'Plant-based ingredients, listed in full on the label.', use:'Apply to clean skin as part of your ritual.', size:'\u2014', pair:null };
  const HEART = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 2.7 4.5 6.4 4.5c2.1 0 3.5 1.2 5.6 3.4 2.1-2.2 3.5-3.4 5.6-3.4 3.7 0 5.5 3.9 4 7.3C19.5 16.4 12 21 12 21z" fill="currentColor"/></svg>';

  function enhanceCards(){
    document.querySelectorAll('.product-card').forEach((card, idx) => {
      if (card.dataset.tv) return;
      const btn = card.querySelector('.add-btn'); if (!btn) return;
      const p = find(btn.dataset.id); if (!p) return;
      card.dataset.tv = '1'; card.dataset.pid = String(p.id); card.dataset.i = idx;
      card.dataset.price = p.price; card.dataset.name = p.name;
      const heart = el('button', 'wish-btn'); heart.type = 'button'; heart.innerHTML = HEART;
      heart.setAttribute('aria-label', 'Save ' + p.name + ' for later');
      card.append(heart);
      const qv = el('button', 'qv-btn', 'Quick view'); qv.type = 'button';
      const concern = card.querySelector('.concern'); (concern || card.querySelector('h3')).after(qv);
      if (window.TV && TV.concern && p.concern === TV.concern) card.append(el('span', 'match-badge', 'Your match'));
    });
    paintHearts();
  }
  function paintHearts(){
    const w = Wish.read();
    document.querySelectorAll('.product-card .wish-btn').forEach(b => {
      const on = w.includes(b.closest('.product-card').dataset.pid);
      b.classList.toggle('on', on); b.setAttribute('aria-pressed', on);
    });
  }

  let overlay, lastFocus, currentId;
  function buildDialog(){
    overlay = el('div', 'qv-overlay'); overlay.hidden = true;
    overlay.innerHTML =
      '<div class="qv" role="dialog" aria-modal="true" aria-labelledby="qvTitle">' +
        '<button type="button" class="qv-close" aria-label="Close">\u00D7</button>' +
        '<div class="qv-art"><svg viewBox="0 0 52 52"></svg></div>' +
        '<div class="qv-body"><p class="qv-concern"></p><h3 id="qvTitle"></h3><p class="qv-farm"></p>' +
          '<dl class="qv-info"><dt>Ingredients</dt><dd class="qv-ing"></dd><dt>How to use</dt><dd class="qv-use"></dd><dt>Size</dt><dd class="qv-size"></dd></dl>' +
          '<p class="qv-pair" hidden>Pairs well with <button type="button" class="qv-pairbtn"></button></p>' +
          '<div class="qv-actions"><span class="qv-price"></span><button type="button" class="btn-primary qv-add">Add to bag</button>' +
          '<button type="button" class="qv-save"></button></div></div></div>';
    document.body.append(overlay);
    overlay.addEventListener('click', e => {
      if (e.target === overlay || e.target.closest('.qv-close')) return closeDialog();
      if (e.target.closest('.qv-pairbtn')) { const n = e.target.closest('.qv-pairbtn').dataset.id; if (n) openDialog(n, true); return; }
      if (e.target.closest('.qv-save')) { Wish.toggle(currentId); return; }
      const add = e.target.closest('.qv-add');
      if (add) {
        if (window.TV && TV.loggedIn === false) { window.location.href = 'login.php'; return; }
        if (TVBag.add(currentId, 1)) { add.textContent = 'Added \u2713'; setTimeout(() => { add.textContent = 'Add to bag'; }, 1400); }
      }
    });
  }
  function paintSave(){
    if (!overlay || overlay.hidden) return;
    const b = $('.qv-save', overlay), on = Wish.has(currentId);
    b.textContent = on ? '\u2665 Saved' : '\u2661 Save for later'; b.classList.toggle('on', on);
  }
  function openDialog(id, keepFocus){
    const p = find(id); if (!p) return;
    if (!overlay) buildDialog();
    if (!keepFocus) lastFocus = document.activeElement;
    currentId = String(p.id);
    const info = infoFor(p), dlg = $('.qv', overlay);
    dlg.style.setProperty('--card-bg', p.card_color || 'var(--peach)');
    $('.qv-art svg', overlay).innerHTML = p.icon_svg || '';
    $('.qv-concern', overlay).textContent = p.concern_label || '';
    $('#qvTitle', overlay).textContent = p.name;
    $('.qv-farm', overlay).textContent = p.farm ? 'Grown by ' + p.farm + (p.farm_location ? ' \u00B7 ' + p.farm_location : '') : '';
    $('.qv-ing', overlay).textContent = info.ing; $('.qv-use', overlay).textContent = info.use; $('.qv-size', overlay).textContent = info.size;
    $('.qv-price', overlay).textContent = '$' + Number(p.price);
    const pair = info.pair && products().find(x => x.name === info.pair);
    $('.qv-pair', overlay).hidden = !pair;
    if (pair) { const pb = $('.qv-pairbtn', overlay); pb.textContent = pair.name; pb.dataset.id = pair.id; }
    overlay.hidden = false; document.body.style.overflow = 'hidden';
    paintSave(); $('.qv-close', overlay).focus();
  }
  function closeDialog(){
    if (!overlay || overlay.hidden) return;
    overlay.hidden = true; document.body.style.overflow = '';
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
})
