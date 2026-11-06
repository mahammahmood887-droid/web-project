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
