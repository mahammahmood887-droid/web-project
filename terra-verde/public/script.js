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
  window.TVBag = { read, write, paint, MAX };

  document.addEventListener('click', e => {
    const add = e.target.closest('.add-btn');
    if (add) {
      if (window.TV && TV.loggedIn === false) { window.location.href = 'login.php'; return; }
      const list = (window.TV && Array.isArray(TV.products)) ? TV.products : [];
      const p = list.find(x => String(x.id) === String(add.dataset.id)); if (!p) return;
      const cart = read(), it = cart.find(i => i.id === String(p.id));
      if (it) it.quantity = Math.min(MAX, it.quantity + 1);
      else cart.push({ id: String(p.id), name: p.name, price: Number(p.price), quantity: 1 });
      write(cart);
      add.textContent = '\u2713'; add.classList.add('added');
      setTimeout(() => { add.textContent = '+'; add.classList.remove('added'); }, 1400);
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

  const grid = document.getElementById('productGrid');
  function renderProducts(){
    grid.innerHTML = products.map(p => `
      <div class="product-card" data-concern="${p.concern}" style="--card-bg:${p.bg}">
        <svg class="icon" viewBox="0 0 52 52">${p.icon}</svg>
        <h3>${p.name}</h3>
        <p class="concern">${p.concernLabel}</p>
        <div class="price-row">
          <span class="price">${p.price}</span>
          <button class="add-btn" data-id="${p.id}" aria-label="Add ${p.name} to bag">+</button>
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
        ${current.opts.map((o,i) => `<button class="quiz-option" data-tag="${o.tag}">${o.label}</button>`).join('')}
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
