'use strict';
(function(){
const $=(s,root=document)=>root.querySelector(s),$$=(s,root=document)=>Array.from(root.querySelectorAll(s));
const dict=window.CP_I18N||{si:{}};
let lang='en',products=window.CP_SEED_PRODUCTS||[],activeCategory=new URLSearchParams(location.search).get('category')||'all';
const el=document.documentElement;
try{lang=localStorage.getItem('cp_language')==='si'?'si':'en'}catch(e){}
const route=(path)=>window.CP_PREVIEW?'offline-preview/'+path.replace(/^product\.php\?id=([a-z0-9-]+)$/,'product-$1.html').replace('.php','.html'):path;
const escapeHTML=(s)=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function tr(key){return lang==='si'?(dict.si?.[key]??key):key}
function showToast(message){const node=$('#toast');if(!node)return;node.textContent=message;node.classList.add('show');clearTimeout(window.__toast);window.__toast=setTimeout(()=>node.classList.remove('show'),3400)}
function setLanguage(next){lang=next;el.lang=next==='si'?'si':'en';document.body.classList.toggle('lang-si',next==='si');try{localStorage.setItem('cp_language',lang)}catch(e){}
$$('[data-t],[data-t-html],[data-t-placeholder]').forEach(node=>{
  if(node.hasAttribute('data-t-html')){if(node.dataset.originalHtml===undefined)node.dataset.originalHtml=node.innerHTML;node.innerHTML=next==='si'?(dict.si?.[node.dataset.tHtml]??node.dataset.originalHtml):node.dataset.originalHtml;}
  if(node.hasAttribute('data-t')){if(node.dataset.originalText===undefined)node.dataset.originalText=node.textContent;node.textContent=next==='si'?(dict.si?.[node.dataset.t]??node.dataset.originalText):node.dataset.originalText;}
  if(node.hasAttribute('data-t-placeholder')){if(node.dataset.originalPlaceholder===undefined)node.dataset.originalPlaceholder=node.getAttribute('placeholder');node.setAttribute('placeholder',next==='si'?(dict.si?.[node.dataset.tPlaceholder]??node.dataset.originalPlaceholder):node.dataset.originalPlaceholder);}
});
$$('.lang-current').forEach(n=>n.textContent=lang==='si'?'සිං':'EN');
$$('[data-product-name]').forEach(n=>{const p=products.find(x=>x.id===n.dataset.productName);if(p)n.textContent=lang==='si'?p.name_si:p.name_en});
$$('[data-product-description]').forEach(n=>{const p=products.find(x=>x.id===n.dataset.productDescription);if(p)n.textContent=lang==='si'?p.description_si:p.description_en});
$$('[data-category-name]').forEach(n=>{n.textContent=catName(n.dataset.categoryName)});
renderProducts();populateQuoteProducts();
}
const catName=(cat)=>lang==='si'?(dict.si?.['cat.'+cat]??cat):({films:'Polythene & Films',bags:'Bags & Pouches',food:'Food Packaging',protective:'Protective & General'}[cat]??cat);
const productName=p=>lang==='si'?p.name_si:p.name_en;
const productDesc=p=>lang==='si'?p.description_si:p.description_en;
function productCard(p){const id=encodeURIComponent(p.id);return `<article class="product-card"><a class="product-card-visual" href="${route(`product.php?id=${id}`)}" aria-label="${escapeHTML(productName(p))}"><img loading="lazy" src="${escapeHTML(p.image)}" alt="${escapeHTML(productName(p))} illustration"><span class="product-badge">${lang==='si'?'නියැදිය':'Illustration'}</span></a><div class="product-card-content"><small>${escapeHTML(catName(p.category))}</small><h3>${escapeHTML(productName(p))}</h3><p>${escapeHTML(productDesc(p))}</p><div class="product-card-actions"><a href="${route(`quote.php?product=${id}`)}">${lang==='si'?'මිල ගණන් විමසන්න':'Request a Quote'}</a><a href="${route(`product.php?id=${id}`)}" aria-label="View details"><svg class="i"><use href="assets/images/icons.svg#arrow-up-right"></use></svg></a></div></div></article>`}
function renderProducts(){const featured=$('#featuredProducts');if(featured && products.length){featured.innerHTML=products.filter(p=>Number(p.featured)===1||p.featured===true).slice(0,4).map(productCard).join('')}
 const grid=$('#catalogueGrid');if(!grid||!products.length)return;
 const term=($('#productSearch')?.value||'').trim().toLowerCase();
 const shown=products.filter(p=>(activeCategory==='all'||p.category===activeCategory)&&[p.name_en,p.name_si,p.description_en,p.description_si].some(t=>(t||'').toLowerCase().includes(term)));
 grid.innerHTML=shown.map(productCard).join('');grid.hidden=shown.length===0;if($('#catalogueEmpty'))$('#catalogueEmpty').hidden=shown.length!==0;
 if($('#productCount'))$('#productCount').innerHTML=`${shown.length} <span>${lang==='si'?'නිෂ්පාදන':'Products'}</span>`;
 $$('.filter-pill').forEach(b=>{let on=b.dataset.category===activeCategory;b.classList.toggle('active',on);b.setAttribute('aria-pressed',String(on))});
}
function populateQuoteProducts(){let select=$('#quote_product');if(!select||!products.length)return;const selected=select.value||new URLSearchParams(location.search).get('product')||'';const first=lang==='si'?'නිෂ්පාදනයක් තෝරන්න':'Select a product';const other=lang==='si'?'වෙනත් / විශේෂ ඇසුරුම්':'Other / Custom Packaging';select.innerHTML=`<option value="">${first}</option>`+products.map(p=>`<option value="${escapeHTML(p.id)}">${escapeHTML(productName(p))}</option>`).join('')+`<option value="other">${other}</option>`;select.value=selected;}
async function loadProducts(){try{let r=await fetch('api/products.php',{cache:'no-store'});if(!r.ok)throw Error('api');let list=await r.json();if(!Array.isArray(list))throw Error('invalid');products=list;}catch(err){try{let r=await fetch('data/products.json');products=await r.json()}catch(e){products=window.CP_SEED_PRODUCTS||[]}}setLanguage(lang)}
function quoteSummary(){const form=$('#quotationForm');if(!form)return '';const d=new FormData(form);const p=products.find(p=>p.id===d.get('product'));const name=p?productName(p):(d.get('product')==='other'?'Custom Packaging':'');const pick=(k)=>String(d.get(k)||'').trim();return `*Ceylon Packaging — Quotation Enquiry*\nName: ${pick('customer_name')}\nPhone: ${pick('phone')}\nEmail: ${pick('email')||'-'}\nCompany: ${pick('company')||'-'}\nProduct: ${name}\nMaterial: ${pick('material')||'-'}\nQuantity: ${pick('quantity')}\nDimensions: ${pick('dimensions')||'-'}\nPrinting: ${pick('printing')||'-'}\nDelivery area: ${pick('delivery_location')||'-'}\nNotes: ${pick('notes')||'-'}\n(Artwork, if any, must be sent separately.)`}
function status(type,msg){const s=$('#quoteStatus');if(!s)return;s.textContent=msg;s.className=`status-message visible ${type}`;s.scrollIntoView({block:'nearest',behavior:'smooth'})}
function initQuote(){const form=$('#quotationForm');if(!form)return;
 const valid=()=>{if(!form.checkValidity()){form.reportValidity();return false}const agree=$('#agree');if(agree&&!agree.checked){agree.focus();status('error',lang==='si'?'කරුණාකර අවසරය ලබාදෙන්න.':'Please agree to the privacy notice.');return false}const file=$('#artwork')?.files?.[0];if(file&&file.size>5*1024*1024){status('error',lang==='si'?'ගොනුව 5MB ට අඩු විය යුතුය.':'File must be under 5 MB.');return false}return true};
 $('#whatsappQuote')?.addEventListener('click',()=>{if(!valid())return;window.open('https://wa.me/94767678937?text='+encodeURIComponent(quoteSummary()),'_blank','noopener,noreferrer')});
 form.addEventListener('submit',async ev=>{ev.preventDefault();if(!valid())return;const btn=$('#submitQuote');btn.disabled=true;const label=$('span',btn);const prev=label.textContent;label.textContent=lang==='si'?'යවමින්...':'Submitting...';
 try{const r=await fetch('api/quote.php',{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});let result=await r.json();if(!r.ok||!result.ok)throw Error(result.error||'Submission could not be completed');status('success',(lang==='si'?'විමසීම සාර්ථකව ලැබුණි. අංකය: ':'Your enquiry was received. Reference: ')+result.reference);form.reset();populateQuoteProducts();}catch(err){status('error',lang==='si'?'මෙම වෙබ් අඩවියේ online පෝරමය තවම සකස් කර නොමැති විය හැක. කරුණාකර WhatsApp භාවිත කරන්න.':'The online form may not be configured yet. Please use the WhatsApp option or contact the company directly.');}finally{btn.disabled=false;label.textContent=prev;}})
}
function initNav(){const header=$('#siteHeader');window.addEventListener('scroll',()=>header?.classList.toggle('scrolled',scrollY>10),{passive:true});
 const toggle=$('.menu-toggle');const close=$('.mobile-close');const backdrop=$('#mobileBackdrop');const nav=$('#mobileNav');const open=()=>{document.body.classList.add('mobile-open');toggle?.setAttribute('aria-expanded','true');close?.focus()},shut=()=>{document.body.classList.remove('mobile-open');toggle?.setAttribute('aria-expanded','false')};
 toggle?.addEventListener('click',open);close?.addEventListener('click',shut);backdrop?.addEventListener('click',shut);$$('.mobile-nav a').forEach(a=>a.addEventListener('click',shut));document.addEventListener('keydown',ev=>{if(ev.key==='Escape')shut()});
 $$('[data-lang-toggle]').forEach(b=>b.addEventListener('click',()=>setLanguage(lang==='en'?'si':'en')));
 $$('.nav-trigger').forEach(b=>b.addEventListener('click',()=>{const par=b.closest('.nav-dropdown');const open=!par.classList.contains('open');$$('.nav-dropdown').forEach(d=>{d.classList.remove('open');d.querySelector('button')?.setAttribute('aria-expanded','false')});par.classList.toggle('open',open);b.setAttribute('aria-expanded',String(open))}));document.addEventListener('click',e=>{if(!e.target.closest('.nav-dropdown'))$$('.nav-dropdown').forEach(d=>d.classList.remove('open'))})
}
function initCatalogue(){const search=$('#productSearch');search?.addEventListener('input',renderProducts);$$('.filter-pill').forEach(b=>b.addEventListener('click',()=>{activeCategory=b.dataset.category;const u=new URL(location.href);if(activeCategory==='all')u.searchParams.delete('category');else u.searchParams.set('category',activeCategory);history.replaceState(null,'',u);renderProducts()}));$('#clearFilters')?.addEventListener('click',()=>{activeCategory='all';if(search)search.value='';renderProducts()})}
function initReveals(){if(!('IntersectionObserver' in window)){ $$('.reveal').forEach(e=>e.classList.add('visible'));return}const io=new IntersectionObserver(entries=>entries.forEach(item=>{if(item.isIntersecting){item.target.classList.add('visible');io.unobserve(item.target)}}),{threshold:.10});$$('.reveal').forEach(item=>io.observe(item));}
document.addEventListener('DOMContentLoaded',()=>{initNav();initCatalogue();initQuote();initReveals();setLanguage(lang);loadProducts();});
})();
