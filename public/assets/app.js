let M=[],E=[],OPT="";
const mu=id=>M.find(m=>m[0]==id),ex=id=>E.find(e=>e[0]==id);
const buildOpt=()=>{OPT=M.map(m=>`<optgroup label="${m[1]}">${E.filter(e=>e[2]==m[0]).map(e=>`<option value="${e[0]}">${e[1]}</option>`).join("")}</optgroup>`).join("")};
const esc=s=>String(s).replace(/[&<>"]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
/* Données de l'utilisateur : chargées depuis MySQL au démarrage, écrites via api.php */
const DB={logs:[],favs:[],progs:[],prof:{},seen:0};
async function api(action,data){try{const r=await fetch("api.php?action="+action,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-Token":window.CSRF||""},body:JSON.stringify(data||{})});
 if(r.status==401){location.href="login.php";return{ok:false}}return await r.json()}catch(e){toast("Erreur réseau : le serveur ne répond pas.");return{ok:false}}}
const logs=()=>DB.logs,favs=()=>DB.favs,progs=()=>DB.progs;
const today=()=>new Date().toISOString().slice(0,10);
const e1=s=>s.w*(1+s.r/30),best=l=>Math.max(...l.sets.map(e1));
const $=s=>document.querySelector(s),app=$("#app");
const fmt=d=>new Date(d+"T12:00").toLocaleDateString("fr-FR",{day:"numeric",month:"short",year:"numeric"});
function toast(t){const d=document.createElement("div");d.textContent=t;$("#toast").append(d);setTimeout(()=>d.remove(),4000)}
function boom(){for(let i=0;i<48;i++){const d=document.createElement("div");d.className="cf";d.style.cssText=`--h:${Math.random()*360};--x:${(Math.random()-.5)*600}px;--y:${Math.random()*500-150}px;--r:${Math.random()*900}deg`;document.body.append(d);setTimeout(()=>d.remove(),1800)}}
function exLogs(id){return logs().filter(l=>l.ex==id).sort((a,b)=>a.date<b.date?-1:1)}
const TH=[[1,0],[3,0],[5,.1],[10,.2],[20,.35]],LN=["Non commencé","Débutant","En progression","Intermédiaire","Confirmé","Expert"];
function level(id){const l=exLogs(id),n=l.length;if(!n)return{lv:0,pct:0,txt:"Enregistre une première séance"};
 const gain=Math.max(...l.map(best))/best(l[0])-1;let lv=1;TH.forEach((t,i)=>{if(n>=t[0]&&gain>=t[1])lv=i+1});
 if(lv==5)return{lv,pct:100,txt:"Niveau maximum atteint"};
 const nx=TH[lv],pct=Math.max(0,Math.min(1,Math.min(n/nx[0],nx[1]?Math.max(gain,0)/nx[1]:1)))*100;
 return{lv,pct:Math.round(pct),txt:"Prochain niveau : "+nx[0]+" séances"+(nx[1]?" et +"+nx[1]*100+" % de force estimée":"")}}
function prs(){let c=0;const by={};logs().sort((a,b)=>a.date<b.date?-1:1).forEach(l=>{const b=best(l);if(by[l.ex]!==undefined&&b>by[l.ex])c++;by[l.ex]=Math.max(by[l.ex]||0,b)});return c}
const RT=[0,100,300,700,1500,3000],RN=["Recrue","Bronze","Argent","Or","Platine","Légende"];
const xp=()=>logs().reduce((a,l)=>a+l.sets.length*10,0)+prs()*50;
function streak(days){const s=new Set(days),f=x=>x.toISOString().slice(0,10);let n=0,d=new Date();if(!s.has(f(d)))d=new Date(Date.now()-864e5);while(s.has(f(d))){n++;d=new Date(d-864e5)}return n}
function chart(pts,lab){if(pts.length<2)return'<p class="mu">Au moins 2 séances sont nécessaires pour tracer une courbe.</p>';
 const W=600,H=220,p=34,mx=Math.max(...pts),mn=Math.min(...pts),r=mx-mn||1;
 const xy=pts.map((v,i)=>[p+i*(W-2*p)/(pts.length-1),H-p-(v-mn)/r*(H-2*p)]),line=xy.map(a=>a.join(",")).join(" "),area=`${xy[0][0]},${H-p} ${line} ${xy[xy.length-1][0]},${H-p}`;
 return`<div class="chartw"><svg class="chart" viewBox="0 0 ${W} ${H}" width="100%" role="img" aria-label="Courbe d’évolution"><defs><linearGradient id="ga" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b6ff3b" stop-opacity=".35"/><stop offset="1" stop-color="#b6ff3b" stop-opacity="0"/></linearGradient></defs>${[0,.5,1].map(f=>`<line x1="${p}" x2="${W-p}" y1="${H-p-f*(H-2*p)}" y2="${H-p-f*(H-2*p)}" stroke="rgba(255,255,255,.08)"/>`).join("")}<polygon points="${area}" fill="url(#ga)"/><polyline fill="none" stroke="#b6ff3b" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" points="${line}"/>${xy.map((a,i)=>`<circle cx="${a[0]}" cy="${a[1]}" r="${i==xy.length-1?6:4}" fill="${i==xy.length-1?"#3bffb0":"#b6ff3b"}" stroke="#0b0d0c" stroke-width="2"><title>${lab[i]} : ${Math.round(pts[i]*10)/10}</title></circle>`).join("")}<text x="4" y="${p+4}" fill="#8a948d" font-size="12">${Math.round(mx)}</text><text x="4" y="${H-p+4}" fill="#8a948d" font-size="12">${Math.round(mn)}</text></svg></div>`}
const ring=(p,c)=>`<svg class="ring" viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.9" stroke="#272c29"/><circle class="r" cx="18" cy="18" r="15.9" pathLength="100" stroke-dasharray="${p} 100"/></svg>`;
function exCard(e){const f=favs().includes(e[0]);return`<a class="card" href="#/e/${e[0]}"><div class="row" style="justify-content:space-between"><span class="ico">${mu(e[2])[2]}</span><button class="fav ${f?"on":""}" onclick="event.preventDefault();fav('${e[0]}')" aria-pressed="${f}" aria-label="${f?"Retirer des favoris":"Ajouter aux favoris"}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></svg>${f?"Favori":"Ajouter"}</button></div><b>${e[1]}</b><div><span class="tag">${mu(e[2])[1]}</span><span class="tag">${e[3]}</span><span class="tag d${["Débutant","Intermédiaire","Avancé"].indexOf(e[4])}">${e[4]}</span></div></a>`}
function fav(id){const f=favs();DB.favs=f.includes(id)?f.filter(x=>x!=id):[...f,id];api("fav_toggle",{ex:id});route(1)}
/* enregistrement commun (fiche exercice + séance guidée) */
function commit(id,date,sets,note){const prev=exLogs(id),pb=prev.length?Math.max(...prev.map(best)):0,old=level(id).lv,L=logs(),rec={id:Date.now()+Math.random().toString(36).slice(2,5),ex:id,date,sets,note};
 L.push(rec);api("log_add",rec).then(r=>{if(r.ok)rec.id=r.id});const pr=prev.length>0&&best(rec)>pb;
 if(pr){toast("Nouveau record personnel !");boom()}else toast("Séance enregistrée");
 const nl=level(id).lv;if(nl>old)toast("Niveau "+nl+" débloqué : "+LN[nl]);return pr}
const V={
profile(){const p=prof(),GL=["Prendre du muscle","Gagner en force","Perdre du poids","Améliorer mon endurance"],gl=!p.goal||GL.includes(p.goal)?GL:[p.goal,...GL];
 return`<h1>Mon <em>profil</em></h1><p class="mu">Ta photo et ton nom sont visibles par les autres membres sur le forum.</p>
 <div class="card pcard"><div class="pav">${avt(p.uid,p.av,p.name||"?","xl")}</div><div class="pavx"><b>Photo de profil</b><span class="mu">JPG, PNG, WebP ou GIF · recadrée en carré</span>
 <div class="row" style="margin-top:10px"><button class="pri" onclick="$('#pf').click()">${p.av?"Changer la photo":"Ajouter une photo"}</button>${p.av?'<button onclick="pAvDel()">Retirer</button>':""}</div>
 <input type="file" id="pf" class="vh" accept="image/jpeg,image/png,image/webp,image/gif" onchange="pAvatar(this)" tabindex="-1" aria-hidden="true"></div></div>
 <div class="card pform"><h2 style="margin-top:0">Informations</h2>
 <label class="mu" for="pn">Nom affiché</label><input id="pn" value="${esc(p.name||"")}" maxlength="20" placeholder="Ton prénom" autocomplete="nickname">
 <label class="mu" for="pg">Objectif</label><select id="pg">${gl.map(g=>`<option${g==p.goal?" selected":""}>${esc(g)}</option>`).join("")}</select>
 <div class="row"><button class="pri" onclick="pSave()">Enregistrer</button></div></div>
 <div class="card pform"><h2 style="margin-top:0">Compte</h2>
 <label class="mu" for="pe">Adresse e-mail</label><input id="pe" type="email" value="${esc(p.email||"")}" maxlength="120" autocomplete="email">
 <label class="mu" for="pw0">Mot de passe actuel</label><input id="pw0" type="password" autocomplete="current-password" placeholder="Requis pour changer l’e-mail ou le mot de passe">
 <label class="mu" for="pw1">Nouveau mot de passe</label><input id="pw1" type="password" minlength="8" autocomplete="new-password" placeholder="Vide = inchangé (8 caractères minimum)">
 <div class="row"><button class="pri" onclick="pAcct()">Mettre à jour le compte</button></div></div>`},
dash(){const L=logs(),days=[...new Set(L.map(l=>l.date))].sort(),last=[...L].sort((a,b)=>a.date<b.date?1:-1)[0];
 if(!L.length)return`<h1>Bienvenue${prof().name?" ":" sur "}<em>${esc(prof().name||"Gym Squale")}</em></h1><p class="mu">Ton coach : programmes sur mesure, séances guidées, records et progression.</p><div class="card empty">Crée ton premier programme ou lance-toi directement.<br><br><a href="#/pg"><button class="pri pulse">Créer un programme</button></a> <a href="#/muscles"><button>Explorer les muscles</button></a></div>`;
 const x=xp(),k=RT.findLastIndex(t=>x>=t),nx=RT[k+1],pct=nx?Math.round((x-RT[k])/(nx-RT[k])*100):100,st=streak(days),wk=days.filter(d=>Date.now()-new Date(d+"T12:00")<7*864e5).length,nm=new Set(L.map(l=>ex(l.ex)[2])).size;
 const vd={};L.forEach(l=>vd[l.date]=(vd[l.date]||0)+l.sets.reduce((s,y)=>s+y.w*y.r,0));const mx=Math.max(...Object.values(vd),1);
 const heat=Array.from({length:84},(_,i)=>{const d=new Date(Date.now()-(83-i)*864e5).toISOString().slice(0,10),v=vd[d];return`<i title="${d}${v?" : "+Math.round(v)+" kg":""}" style="${v?`background:var(--g);opacity:${.3+.7*v/mx}`:""}"></i>`}).join("");
 const vol=days.slice(-8).map(d=>vd[d]),B=[["Première séance",1],["10 séances",days.length>=10],["Premier record",prs()>0],["Série de 3 jours",st>=3],["5 muscles travaillés",nm>=5],["Créateur de programme",progs().length>0]];
 return`<h1>${prof().name?"Salut <em>"+esc(prof().name)+"</em>":"Ton <em>dashboard</em>"}</h1>
 <div class="card hero"><div class="hero-top"><div><div class="rank">${RN[k]}</div><span class="mu"><span class="cu" data-n="${x}">0</span> XP${nx?" · prochain rang à "+nx+" XP":" · rang maximum"}</span></div><div class="streak"><b>${st}</b><span class="mu">jour${st>1?"s":""} de suite</span></div></div><div class="ladder" style="--p:${(k+(nx?pct/100:0))/(RN.length-1)*100}%"><div class="lt"><i></i></div>${RN.map((n,i)=>`<div class="rg ${i<=k?"done":""} ${i==k?"cur":""}" style="--i:${i}"><s></s><span>${n}</span></div>`).join("")}</div></div>
 <div class="card strip"><div><b class="cu" data-n="${days.length}">0</b><span>séances</span></div><div><b class="cu" data-n="${new Set(L.map(l=>l.ex)).size}">0</b><span>exercices suivis</span></div><div><b class="cu" data-n="${prs()}">0</b><span>records battus</span></div><div><b class="cu" data-n="${wk}">0</b><span>séances sur 7 jours</span></div></div>
 <h2>Régularité (12 dernières semaines)</h2><div class="card"><div class="heat">${heat}</div></div>
 <h2>Volume par séance (kg)</h2><div class="card">${chart(vol,days.slice(-8).map(fmt))}</div>
 <h2>Succès</h2><div class="card">${B.map(b=>`<span class="tag ${b[1]?"ok":""}">${b[1]?"✓":"·"} ${b[0]}</span>`).join("")}</div>
 <h2>À reprendre</h2><a class="card" href="#/e/${last.ex}">${ex(last.ex)[1]} — dernière fois : ${last.sets.map(s=>s.w+"kg×"+s.r).join(", ")}</a>
 ${progs().length?`<h2>Lancer une séance</h2><div class="row">${progs().flatMap(p=>p.days.map((d,i)=>`<button onclick="go('${p.id}',${i})">${esc(p.name)} · ${esc(d.n)}</button>`)).join("")}</div>`:""}`},
pg(){const P=progs();
 return`<h1>Mes <em>programmes</em></h1><p class="mu">Crée ton propre programme, génère-en un automatiquement ou importe celui d’un ami.</p>
 <div class="row" style="margin:12px 0"><button class="pri" onclick="newPg()">+ Créer mon programme</button></div>
 ${window.shr?`<div class="card"><b>Code de partage</b><p class="mu" style="font-size:13px">Envoie ce code à un ami : il pourra l’importer.</p><textarea id="sh" rows="3" readonly onclick="this.select()">${window.shr}</textarea><button onclick="$('#sh').select();try{navigator.clipboard.writeText(window.shr)}catch(e){};toast('Code copié')" style="margin-top:8px">Copier</button></div>`:""}
 <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))"><div class="card"><b>Générateur intelligent</b><div class="row" style="margin:10px 0"><select id="gg"><option>Hypertrophie</option><option>Force</option><option>Endurance</option></select><select id="gd"><option value="2">2 jours</option><option value="3" selected>3 jours</option><option value="4">4 jours</option></select><select id="gl"><option value="0">Débutant</option><option value="1">Intermédiaire</option><option value="2">Avancé</option></select></div><button class="pri" onclick="gen()">Générer</button></div>
 <div class="card"><b>Importer un code</b><textarea id="ic" rows="2" placeholder="Colle le code ici" style="margin:10px 0"></textarea><button onclick="imp()">Importer</button></div></div>
 ${P.length?P.map(p=>`<div class="card" style="margin-top:14px"><div class="row" style="justify-content:space-between"><b style="font-size:18px">${esc(p.name)}</b><span class="tag ok">${esc(p.goal)}</span></div>${p.days.map((d,i)=>`<div class="it"><span><b>${esc(d.n)}</b><br><span class="mu" style="font-size:13px">${d.items.map(t=>ex(t.ex)[1]).join(" · ")}</span></span><button class="pri" onclick="go('${p.id}',${i})">Lancer</button></div>`).join("")}<div class="row" style="margin-top:10px"><button onclick="edit('${p.id}')">Modifier</button><button onclick="share('${p.id}')">Partager</button><button onclick="delP('${p.id}')">Supprimer</button></div></div>`).join(""):'<div class="card empty" style="margin-top:14px">Aucun programme pour l’instant.</div>'}`},
nw(){if(!pd)newPg(1);return`<a href="#/pg" class="mu">← Programmes</a><h1>${pd.id?"Modifier":"Créer"} <em>mon programme</em></h1>
 <div class="row" style="margin:12px 0"><input placeholder="Nom du programme" value="${esc(pd.name)}" oninput="pd.name=this.value" style="flex:2"><select oninput="pd.goal=this.value" style="flex:1">${["Hypertrophie","Force","Endurance","Perte de poids"].map(g=>`<option ${g==pd.goal?"selected":""}>${g}</option>`).join("")}</select></div>
 ${pd.days.map((d,di)=>`<div class="card" style="margin-bottom:12px"><div class="row"><input value="${esc(d.n)}" oninput="pd.days[${di}].n=this.value" style="flex:1"><button onclick="pd.days.splice(${di},1);route(1)">Supprimer le jour</button></div>
 ${d.items.map((t,ii)=>`<div class="it"><span>${ex(t.ex)[1]} <span class="tag">${t.s} × ${t.r}</span></span><button onclick="pd.days[${di}].items.splice(${ii},1);route(1)">×</button></div>`).join("")}
 <div class="row" style="margin-top:10px"><select id="se${di}" style="flex:3;min-width:150px">${OPT}</select><input id="ss${di}" type="number" min="1" value="3" style="width:70px" title="Séries" placeholder="Séries" aria-label="Séries"><input id="sr${di}" type="number" min="1" value="10" style="width:70px" title="Répétitions" placeholder="Reps" aria-label="Répétitions"><button onclick="addIt(${di})">+ Ajouter</button></div></div>`).join("")}
 <div class="row"><button onclick="pd.days.push({n:'Jour '+(pd.days.length+1),items:[]});route(1)">+ Ajouter un jour</button><button class="pri" onclick="savePg()">Enregistrer le programme</button></div>`},
run(){if(!rs)return V.pg();const p=progs().find(x=>x.id==rs.pid);if(!p){rs=null;return V.pg()}const d=p.days[rs.di],n=d.items.length;
 if(rs.i>=n)return`<h1>Séance <em>terminée</em></h1><div class="card empty"><div class="big">+${rs.xp} XP</div><p>${rs.n} exercice${rs.n>1?"s":""} validé${rs.n>1?"s":""}${rs.pr?" · "+rs.pr+" record"+(rs.pr>1?"s":"")+" battu"+(rs.pr>1?"s":""):""}. Bien joué !</p><a href="#/dash"><button class="pri">Voir mon dashboard</button></a></div>`;
 const t=d.items[rs.i],e=ex(t.ex),l=exLogs(t.ex).pop();
 return`<a href="#/pg" class="mu" onclick="rs=null">← Quitter la séance</a><h1>${esc(p.name)} · <em>${esc(d.n)}</em></h1><div class="bar" style="margin:10px 0"><i style="width:${rs.i/n*100}%"></i></div><span class="mu">Exercice ${rs.i+1} sur ${n}</span>
 <div class="card" style="margin-top:12px"><span class="ico">${mu(e[2])[2]}</span><h2 style="margin:0">${e[1]}</h2><span class="tag ok">Objectif : ${t.s} × ${t.r}</span><span class="tag">${e[3]}</span><ol>${e[5].map(s=>`<li>${s}</li>`).join("")}</ol><p><b>Conseil :</b> ${e[6]}</p></div>
 <div class="card" style="margin-top:14px">${l?`<p class="mu" style="font-size:13px">Dernière fois : ${l.sets.map(s=>s.w+"×"+s.r).join(" · ")}</p>`:""}
 ${rs.sets.map((s,i)=>`<div class="sr" style="grid-template-columns:34px 1fr 1fr"><span>S${i+1}</span><input type="number" inputmode="decimal" min="0" step="0.5" placeholder="Poids (kg)" value="${s.w}" oninput="rs.sets[${i}].w=this.value"><input type="number" inputmode="numeric" min="1" placeholder="Reps" value="${s.r}" oninput="rs.sets[${i}].r=this.value"></div>`).join("")}
 <div class="row" style="margin-top:10px"><button class="pri" onclick="runOk()">Valider et repos</button><button onclick="runOk(1)">Passer</button></div><div id="tm" class="big" style="margin-top:10px"></div></div>`},
muscles(){const L=logs();return`<h1>Choisis ton <em>groupe musculaire</em></h1><div class="grid">${M.map(m=>{const n=E.filter(e=>e[2]==m[0]).length,d=new Set(L.filter(l=>ex(l.ex)[2]==m[0]).map(l=>l.ex)).size;return`<a class="card" href="#/m/${m[0]}"><div class="ico">${m[2]}</div><b>${m[1]}</b><div class="mu" style="font-size:13px">${m[3]}</div><p>${n} exercices</p><div class="bar"><i style="width:${d/n*100}%"></i></div><small class="mu">${d}/${n} exercices pratiqués</small></a>`}).join("")}</div>`},
m(id){const m=mu(id);return`<a href="#/muscles" class="mu">← Muscles</a><h1><em>${m[1]}</em></h1><p class="mu">${m[3]}</p><div class="grid">${E.filter(e=>e[2]==id).map(exCard).join("")}</div>`},
ex(){return`<h1>Bibliothèque d’<em>exercices</em></h1><div class="row" style="margin-top:12px"><input id="q" placeholder="Rechercher…" oninput="filt()" style="flex:2;min-width:140px"><select id="fm" onchange="filt()" style="flex:1"><option value="">Tous muscles</option>${M.map(m=>`<option value="${m[0]}">${m[1]}</option>`).join("")}</select><select id="fe" onchange="filt()" style="flex:1"><option value="">Tout matériel</option>${[...new Set(E.map(e=>e[3]))].map(x=>`<option>${x}</option>`).join("")}</select><select id="fd" onchange="filt()" style="flex:1"><option value="">Difficulté</option><option>Débutant</option><option>Intermédiaire</option><option>Avancé</option></select><label><input type="checkbox" id="ff" onchange="filt()" style="width:auto"> Favoris</label></div><div class="grid" id="exl"></div>`},
hist(){const L=[...logs()].sort((a,b)=>a.date<b.date?1:-1);if(!L.length)return`<h1><em>Entraînements</em></h1><div class="card empty">Aucune séance enregistrée.</div>`;
 const days=[...new Set(L.map(l=>l.date))];return`<h1>Mes <em>entraînements</em></h1><div class="row" style="margin:10px 0"><button onclick="csv()">Exporter en CSV</button></div>${days.map(d=>`<div class="card" style="margin-bottom:12px"><b>${fmt(d)}</b>${L.filter(l=>l.date==d).map(l=>`<div style="margin-top:10px"><a href="#/e/${l.ex}" style="color:var(--g)">${ex(l.ex)[1]}</a> <span class="mu">(${mu(ex(l.ex)[2])[1]})</span><br>${l.sets.map((s,i)=>`<span class="tag">S${i+1} : ${s.w} kg × ${s.r}</span>`).join("")}${l.note?`<br><i class="mu">${esc(l.note)}</i>`:""} <button class="fav" onclick="del('${l.id}')">Supprimer</button></div>`).join("")}</div>`).join("")}`},
prog(){const ids=[...new Set(logs().map(l=>l.ex))];if(!ids.length)return`<h1><em>Progression</em></h1><div class="card empty">Enregistre des séances pour voir tes courbes et tes niveaux.</div>`;
 const id=window.pe&&ids.includes(window.pe)?window.pe:ids[0],l=exLogs(id),lv=level(id);
 return`<h1><em>Progression</em></h1><select onchange="pe=this.value;route(1)" style="margin:10px 0">${ids.map(i=>`<option value="${i}" ${i==id?"selected":""}>${ex(i)[1]}</option>`).join("")}</select>
 <div class="card"><h2 style="margin-top:0">Force estimée (1RM) par séance, kg</h2>${chart(l.map(best),l.map(x=>fmt(x.date)))}</div>
 <div class="card" style="margin-top:14px"><b>Niveau ${lv.lv} · ${LN[lv.lv]}</b><div class="bar" style="margin:8px 0"><i style="width:${lv.pct}%"></i></div><span class="mu">${lv.pct} % — ${lv.txt}</span><p class="mu" style="font-size:13px">Niveaux basés sur ton nombre de séances et ton évolution personnelle : ce n’est pas une mesure scientifique de ta force.</p></div>
 <h2>Niveaux par exercice</h2><table>${ids.map(i=>`<tr><td>${ex(i)[1]}</td><td>Niv. ${level(i).lv}</td><td>Record : ${Math.round(Math.max(...exLogs(i).map(best)))} kg est.</td></tr>`).join("")}</table>`}
};
let draft=[{w:"",r:""}],rest=null,pd=null,rs=null;
V.e=function(id){const e=ex(id);if(!e)return"";const l=exLogs(id),lv=level(id),lastL=l[l.length-1];
 return`<a href="#/m/${e[2]}" class="mu">← ${mu(e[2])[1]}</a><h1>${e[1]}</h1><span class="tag">${mu(e[2])[1]}</span><span class="tag">${e[3]}</span><span class="tag d${["Débutant","Intermédiaire","Avancé"].indexOf(e[4])}">${e[4]}</span>
 <div class="card" style="margin:14px 0"><b>Exécution</b><ol>${e[5].map(s=>`<li>${s}</li>`).join("")}</ol><p><b>Conseil :</b> ${e[6]}</p><p class="mu" style="font-size:13px">Repères indicatifs : 3-4 séries de 8-12 répétitions, 60-120 s de repos. À adapter à ton niveau. Expire pendant l’effort, inspire à la descente.</p></div>
 <div class="card"><b>Niveau ${lv.lv} · ${LN[lv.lv]}</b><div class="bar" style="margin:8px 0"><i style="width:${lv.pct}%"></i></div><span class="mu">${lv.txt}</span></div>
 <div class="card" style="margin-top:14px"><b>Enregistrer ma séance</b>${lastL?`<p class="mu" style="font-size:13px">Dernière fois (${fmt(lastL.date)}) : ${lastL.sets.map(s=>s.w+"×"+s.r).join(" · ")}</p>`:""}
 <div class="row" style="margin:8px 0"><input type="date" id="dt" value="${today()}" style="max-width:170px"><select id="unit" style="max-width:90px"><option>kg</option><option>lb</option></select></div>
 <div id="sets">${draft.map((s,i)=>`<div class="sr"><span>S${i+1}</span><input type="number" inputmode="decimal" min="0" step="0.5" placeholder="Poids" value="${s.w}" oninput="draft[${i}].w=this.value"><input type="number" inputmode="numeric" min="1" step="1" placeholder="Reps" value="${s.r}" oninput="draft[${i}].r=this.value"><button onclick="rmSet(${i})" aria-label="Supprimer la série">Suppr.</button></div>`).join("")}</div>
 <textarea id="nt" rows="2" placeholder="Commentaire (facultatif)"></textarea>
 <div class="row" style="margin-top:10px"><button onclick="addSet()">+ Ajouter une série</button><button onclick="timer(90)">Repos 90 s</button><button class="pri" onclick="saveS('${id}')">Enregistrer ma séance</button></div><div id="tm" class="big" style="margin-top:10px"></div></div>`};
function addSet(){const l=draft[draft.length-1];draft.push({w:l?l.w:"",r:l?l.r:""});route(1)}
function rmSet(i){draft.splice(i,1);if(!draft.length)draft=[{w:"",r:""}];route(1)}
function timer(s){clearInterval(rest);const t=$("#tm");if(!t)return;let n=s;t.textContent="Repos : "+n;rest=setInterval(()=>{n--;t.textContent=n>0?"Repos : "+n:"Go !";if(n<=0)clearInterval(rest)},1000)}
function saveS(id){const f=$("#unit").value=="lb"?.4536:1,sets=draft.filter(s=>s.w!==""||s.r!=="").map(s=>({w:Math.round(parseFloat(s.w)*f*10)/10,r:parseInt(s.r)}));
 if(!sets.length||sets.some(s=>!(s.w>=0)||!(s.r>0)))return toast("Renseigne un poids et des répétitions valides pour chaque série.");
 const date=$("#dt").value;if(!date)return toast("Choisis une date.");
 commit(id,date,sets,$("#nt").value.trim());draft=[{w:"",r:""}];clearInterval(rest);route(1)}
function del(id){if(confirm("Supprimer cet enregistrement ?")){DB.logs=DB.logs.filter(l=>l.id!=id);api("log_delete",{id});route(1)}}
function csv(){const r=["date;exercice;muscle;serie;poids_kg;reps;commentaire"];logs().forEach(l=>l.sets.forEach((s,i)=>r.push([l.date,ex(l.ex)[1],mu(ex(l.ex)[2])[1],i+1,s.w,s.r,'"'+(l.note||"").replace(/"/g,"'")+'"'].join(";"))));
 const a=document.createElement("a");a.href="data:text/csv;charset=utf-8,"+encodeURIComponent(r.join("\n"));a.download="gym-squale.csv";a.click()}
function filt(){const q=$("#q").value.toLowerCase(),m=$("#fm").value,q2=$("#fe").value,d=$("#fd").value,f=$("#ff").checked,F=favs();
 const r=E.filter(e=>(!q||e[1].toLowerCase().includes(q))&&(!m||e[2]==m)&&(!q2||e[3]==q2)&&(!d||e[4]==d)&&(!f||F.includes(e[0])));
 $("#exl").innerHTML=r.length?r.map(exCard).join(""):'<div class="empty" style="grid-column:1/-1">Aucun exercice trouvé.</div>'}
/* programmes personnalisés */
function newPg(q){pd={name:"",goal:"Hypertrophie",days:[{n:"Jour 1",items:[]}]};if(!q)location.hash="#/nw"}
function edit(id){pd=JSON.parse(JSON.stringify(progs().find(p=>p.id==id)));location.hash="#/nw"}
function addIt(di){const s=+$("#ss"+di).value,r=+$("#sr"+di).value;if(!(s>0&&r>0))return toast("Séries et répétitions invalides.");pd.days[di].items.push({ex:$("#se"+di).value,s,r});route(1)}
async function savePg(){pd.days=pd.days.filter(d=>d.items.length);if(!pd.name.trim())return toast("Donne un nom à ton programme.");if(!pd.days.length)return toast("Ajoute au moins un exercice.");
 const r=await api("program_save",pd);if(!r.ok)return toast(r.error||"Enregistrement impossible.");
 pd.id=r.id;const P=progs(),i=P.findIndex(p=>p.id==pd.id);if(i>=0)P[i]=pd;else P.push(pd);
 pd=null;toast("Programme enregistré");boom();location.hash="#/pg"}
function delP(id){if(confirm("Supprimer ce programme ?")){DB.progs=DB.progs.filter(p=>p.id!=id);api("program_delete",{id});route(1)}}
function gen(){const g=$("#gg").value,n=+$("#gd").value,lv=+$("#gl").value,rk={"Débutant":0,"Intermédiaire":1,"Avancé":2};
 const sp={2:[["pec","dos","qua","abs"],["epa","isc","bic","tri","mol"]],3:[["pec","epa","tri"],["dos","bic","abs"],["qua","isc","fes","mol"]],4:[["pec","tri"],["dos","bic"],["qua","mol"],["epa","isc","fes","abs"]]}[n],sr={Hypertrophie:[4,10],Force:[5,5],Endurance:[3,15]}[g];
 pd={name:"Programme "+g+" "+n+"j",goal:g,days:sp.map((ms,i)=>({n:"Jour "+(i+1),items:ms.flatMap(m=>E.filter(e=>e[2]==m&&rk[e[4]]<=lv).sort(()=>Math.random()-.5).slice(0,m=="abs"||m=="mol"?1:2).map(e=>({ex:e[0],s:sr[0],r:sr[1]})))}))};
 toast("Programme généré : ajuste-le puis enregistre-le");if(location.hash=="#/nw")route();else location.hash="#/nw"}
function share(id){const p=progs().find(x=>x.id==id);window.shr=btoa(unescape(encodeURIComponent(JSON.stringify({name:p.name,goal:p.goal,days:p.days}))));route(1)}
function imp(){try{const o=JSON.parse(decodeURIComponent(escape(atob($("#ic").value.trim()))));
 const p={name:String(o.name).slice(0,40),goal:String(o.goal).slice(0,20),days:o.days.slice(0,7).map(d=>({n:String(d.n).slice(0,30),items:d.items.filter(i=>ex(i.ex)).slice(0,15).map(i=>({ex:i.ex,s:+i.s||3,r:+i.r||10}))})).filter(d=>d.items.length)};
 if(!p.days.length)throw 0;api("program_save",p).then(r=>{if(!r.ok)return toast(r.error||"Enregistrement impossible.");p.id=r.id;progs().push(p);toast("Programme importé");route(1)})}catch(e){toast("Code invalide.")}}
/* séance guidée */
function initSets(){const p=progs().find(x=>x.id==rs.pid),t=p.days[rs.di].items[rs.i],l=exLogs(t.ex).pop();
 rs.sets=Array.from({length:t.s},(_,k)=>({w:l?(l.sets[k]||l.sets[l.sets.length-1]).w:"",r:t.r}))}
function go(pid,di){rs={pid,di,i:0,xp:0,pr:0,n:0};initSets();location.hash="#/run";if(location.hash=="#/run")route()}
function runOk(skip){const p=progs().find(x=>x.id==rs.pid),d=p.days[rs.di],t=d.items[rs.i];
 if(!skip){const sets=rs.sets.filter(s=>s.w!==""&&s.r!=="").map(s=>({w:+s.w,r:parseInt(s.r)}));
  if(!sets.length||sets.some(s=>!(s.w>=0)||!(s.r>0)))return toast("Renseigne poids et répétitions.");
  const pr=commit(t.ex,today(),sets,"");rs.xp+=sets.length*10+(pr?50:0);rs.n++;if(pr)rs.pr++}
 rs.i++;const more=rs.i<d.items.length;if(more)initSets();else boom();route(1);if(!skip&&more)timer(90)}
function route(keep){const h=(location.hash||"#/dash").split("/"),k=V[h[1]]?h[1]:"dash",a=h[2];
 document.querySelectorAll("nav a").forEach(n=>n.classList.toggle("on",n.dataset.r==k||(k=="m"&&n.dataset.r=="muscles")||(k=="e"&&n.dataset.r=="ex")||((k=="nw"||k=="run")&&n.dataset.r=="pg")));
 {const an=document.querySelector("nav a.on");if(an&&an.scrollIntoView)an.scrollIntoView({block:"nearest",inline:"center"})}const y=scrollY;app.classList.toggle("nk",!!keep);app.innerHTML=V[k](a);if(k=="ex")filt();
 app.querySelectorAll(".card").forEach((c,i)=>c.style.setProperty("--i",Math.min(i,14)));
 document.querySelectorAll(".cu").forEach(e=>{const n=+e.dataset.n,t0=performance.now();(function f(t){const p=Math.min(1,(t-t0)/900);e.textContent=Math.round(n*(1-Math.pow(1-p,3)));if(p<1)requestAnimationFrame(f)})(t0)});
 scrollTo(0,keep?y:0)}
/* forum : fil commun à tous les membres */
const FR={cat:"",mine:false,posts:[],more:false,open:new Set(),pend:[],up:0},FCATS=["Progression","Question","Conseil","Motivation"];let fq=0;
const ago=ts=>{const s=Math.max(0,Date.now()/1e3-ts);if(s<60)return"à l’instant";if(s<3600)return"il y a "+Math.floor(s/60)+" min";if(s<86400)return"il y a "+Math.floor(s/3600)+" h";if(s<172800)return"hier";return new Date(ts*1e3).toLocaleDateString("fr-FR",{day:"numeric",month:"short"})};
const ini=n=>esc([...String(n||"?").trim()][0]||"?").toUpperCase();
const FR_MAX=4,UMAX=4*1048576,sz=n=>n<1048576?Math.max(1,Math.round(n/1024))+" Ko":(n/1048576).toFixed(1).replace(".",",")+" Mo";
const USER_ICO='<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>',FILE_ICO='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>',CLIP_ICO='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5 12.1 19.4a5 5 0 0 1-7-7L13 4.5a3.3 3.3 0 0 1 4.7 4.7l-7.9 7.9a1.7 1.7 0 0 1-2.4-2.4l7.1-7.1"/></svg>';
async function upl(fd){try{const r=await fetch("upload.php",{method:"POST",headers:{"X-CSRF-Token":window.CSRF||""},body:fd});
 if(r.status==401){location.href="login.php";return{ok:false}}
 try{return await r.json()}catch(e){return{ok:false,error:r.status==413?"Fichier trop volumineux (4 Mo maximum).":"Envoi impossible (réponse inattendue du serveur)."}}}
 catch(e){return{ok:false,error:"Erreur réseau : le serveur ne répond pas."}}}
const avt=(u,v,n,c)=>v?`<span class="avatar ${c||""}"><img src="file.php?t=a&id=${+u}&v=${+v}" alt="" loading="lazy"></span>`:`<span class="avatar ${c||""}" aria-hidden="true">${ini(n)}</span>`;
function navAv(){const a=$("#navav");if(!a)return;const p=prof();a.innerHTML=p.av?`<img src="file.php?t=a&id=${+p.uid}&v=${+p.av}" alt="">`:USER_ICO;a.classList.toggle("has",!!p.av)}
function filesHtml(fs){if(!fs||!fs.length)return"";const u=f=>"file.php?t=p&id="+f.id,im=fs.filter(f=>f.mime.startsWith("image/")),ot=fs.filter(f=>!f.mime.startsWith("image/"));
 return`<div class="pfiles">${im.length?`<div class="pimgs n${Math.min(im.length,4)}">${im.map(f=>`<a href="${u(f)}" target="_blank" rel="noopener" aria-label="Ouvrir ${esc(f.name)}"><img src="${u(f)}" alt="${esc(f.name)}" loading="lazy"></a>`).join("")}</div>`:""}${ot.map(f=>f.mime=="video/mp4"?`<video class="pvid" controls preload="metadata" src="${u(f)}" aria-label="${esc(f.name)}"></video>`:`<a class="fchip" href="${u(f)}" target="_blank" rel="noopener">${FILE_ICO}<span><b>${esc(f.name)}</b><span class="mu">${sz(f.size)} · PDF</span></span></a>`).join("")}</div>`}
function attHtml(a){const e=a&&ex(a.ex);if(!e)return"";return`<div class="att"><span class="ico">${mu(e[2])[2]}</span><div><b>${e[1]}</b> <span class="mu">· ${fmt(a.date)}</span><div>${(a.sets||[]).map((s,i)=>`<span class="tag">S${i+1} : ${+s.w} kg × ${+s.r}</span>`).join("")}</div></div></div>`}
const HEART='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-4.4-9-9.2C1.4 8 3.8 4.5 7.2 4.5c1.9 0 3.5 1 4.8 2.8 1.3-1.8 2.9-2.8 4.8-2.8 3.4 0 5.8 3.5 4.2 7.3C19 16.6 12 21 12 21z"/></svg>',BUBBLE='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg>';
function comHtml(p){return`<div class="coms">${p.comments.map(c=>`<div class="com">${avt(c.uid,c.av,c.author,"sm")}<div><b>${esc(c.author)}</b> <span class="mu">${ago(c.ts)}</span><p>${esc(c.body)}</p></div>${c.del?`<button class="fav" onclick="fcdel(${p.id},${c.id})" aria-label="Supprimer le commentaire">Supprimer</button>`:""}</div>`).join("")}<div class="row" style="margin-top:8px"><input id="ci${p.id}" placeholder="Écrire un commentaire…" maxlength="500" aria-label="Commentaire" onkeydown="if(event.key=='Enter')fcom(${p.id})" style="flex:1;min-width:160px"><button class="pri" onclick="fcom(${p.id})">Commenter</button></div></div>`}
function postHtml(p){const o=FR.open.has(p.id),n=p.comments.length;return`<article class="card post"><div class="phead">${avt(p.uid,p.av,p.author)}<div class="pmeta"><b>${esc(p.author)}</b><span class="mu">${ago(p.ts)}</span></div><span class="tag ${p.cat=="Progression"?"ok":""}">${p.cat}</span></div><p class="pbody">${esc(p.body)}</p>${attHtml(p.att)}${filesHtml(p.files)}<div class="pact"><button class="pa ${p.liked?"on":""}" onclick="flike(${p.id})" aria-pressed="${p.liked}" aria-label="${p.liked?"Retirer mon j’aime":"J’aime"}">${HEART}<span>${p.likes}</span></button><button class="pa" onclick="ftog(${p.id})" aria-expanded="${o}">${BUBBLE}<span>${n} commentaire${n>1?"s":""}</span></button>${p.mine?`<button class="pa del" onclick="fdel(${p.id})">Supprimer</button>`:""}</div>${o?comHtml(p):""}</article>`}
V.forum=function(){setTimeout(()=>{forumLoad(1);fpendRender()},0);
 const ls=[...logs()].filter(l=>/^\d+$/.test(String(l.id))).sort((a,b)=>a.date<b.date?1:a.date>b.date?-1:b.id-a.id).slice(0,15);
 return`<h1>Le <em>forum</em></h1><p class="mu">Partage tes avancées, pose tes questions, encourage les autres membres.</p>
 <div class="card compose"><label class="mu" for="fb">Ta publication</label><textarea id="fb" rows="3" maxlength="1000" placeholder="Raconte ta dernière séance, un record, une question…" oninput="fcount()"></textarea><div class="fcount mu" id="fcn">0 / 1000</div>
 <div class="row"><select id="fc" aria-label="Catégorie" style="flex:1;min-width:140px">${FCATS.map(c=>`<option>${c}</option>`).join("")}</select><select id="fl" aria-label="Joindre une séance" style="flex:2;min-width:190px"><option value="">Sans séance jointe</option>${ls.map(l=>`<option value="${l.id}">${fmt(l.date)} · ${ex(l.ex)[1]} · ${l.sets.length} série${l.sets.length>1?"s":""}</option>`).join("")}</select><button type="button" class="attach" onclick="$('#ff').click()" aria-label="Joindre un fichier">${CLIP_ICO}<span>Joindre</span></button><input type="file" id="ff" class="vh" multiple accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,video/mp4" onchange="fpick(this)" tabindex="-1" aria-hidden="true"><button class="pri" onclick="fpost()">Publier</button></div><div class="fpend" id="fpend" aria-live="polite"></div><div class="fhint mu">Images, PDF ou vidéo MP4 · 4 fichiers max · 4 Mo chacun</div></div>
 <div class="chips" role="group" aria-label="Filtrer les publications">${["",...FCATS].map(c=>`<button class="chip" aria-pressed="${!FR.mine&&FR.cat==c}" onclick="fcat('${c}')">${c||"Tout"}</button>`).join("")}<button class="chip" aria-pressed="${FR.mine}" onclick="fmine()">Mes publications</button></div>
 <div id="feed"><div class="empty">Chargement…</div></div><div id="fmore" style="text-align:center;margin-top:14px"></div>`};
async function forumLoad(reset){const q=++fq,before=reset||!FR.posts.length?0:FR.posts[FR.posts.length-1].id;
 const r=await api("forum_list",{cat:FR.cat,mine:FR.mine,before});if(q!=fq)return;
 if(!r.ok){const f=$("#feed");if(f)f.innerHTML=`<div class="card empty">${esc(r.error||"Impossible de charger le forum.")}</div>`;return}
 FR.posts=before?FR.posts.concat(r.posts):r.posts;FR.more=r.more;forumRender()}
function forumRender(){const f=$("#feed");if(!f)return;const dr={},fo=document.activeElement&&document.activeElement.id;
 document.querySelectorAll('[id^="ci"]').forEach(i=>dr[i.id]=i.value);
 f.innerHTML=FR.posts.length?FR.posts.map(postHtml).join(""):`<div class="card empty">${FR.mine?"Tu n’as pas encore publié.":FR.cat?"Aucune publication dans cette catégorie.":"Aucune publication pour l’instant.<br>Sois le premier à partager ta progression."}</div>`;
 Object.keys(dr).forEach(k=>{const i=$("#"+k);if(i)i.value=dr[k]});const a=fo&&$("#"+fo);if(a&&a.id.startsWith("ci"))a.focus();
 const m=$("#fmore");if(m)m.innerHTML=FR.more?'<button onclick="forumLoad()">Voir plus de publications</button>':""}
function fpendRender(){const e=$("#fpend");if(!e)return;
 e.innerHTML=FR.pend.map(f=>`<span class="pchip">${f.mime.startsWith("image/")?`<img src="file.php?t=p&id=${f.id}" alt="">`:FILE_ICO}<span class="pn">${esc(f.name)}</span><span class="mu">${sz(f.size)}</span><button type="button" class="px" onclick="fpendDel(${f.id})" aria-label="Retirer ${esc(f.name)}">×</button></span>`).join("")+(FR.up?`<span class="pchip busy">Envoi en cours…</span>`:"")}
async function fpick(inp){const fs=[...inp.files];inp.value="";
 for(const f of fs){if(FR.pend.length+FR.up>=FR_MAX){toast("Maximum "+FR_MAX+" fichiers par publication.");break}
  if(f.size>UMAX){toast(f.name+" dépasse 4 Mo.");continue}
  const fd=new FormData();fd.append("file",f);fd.append("kind","post");FR.up++;fpendRender();
  const r=await upl(fd);FR.up--;if(r.ok)FR.pend.push(r.file);else toast(r.error||"Envoi impossible.");fpendRender()}}
function fpendDel(id){FR.pend=FR.pend.filter(f=>f.id!=id);fpendRender();api("upload_discard",{id})}
function fcount(){const t=$("#fb"),c=$("#fcn");if(t&&c)c.textContent=t.value.length+" / 1000"}
async function fpost(){const b=$("#fb").value.trim();if(!b&&!FR.pend.length)return toast("Écris quelque chose ou joins un fichier avant de publier.");if(FR.up)return toast("Patiente, l’envoi des fichiers n’est pas terminé.");const lg=$("#fl").value;
 const r=await api("forum_post",{body:b,cat:$("#fc").value,log_id:lg?+lg:undefined,file_ids:FR.pend.map(f=>f.id)});if(!r.ok)return toast(r.error||"Publication impossible.");FR.pend=[];
 toast("Publication ajoutée");FR.cat="";FR.mine=false;route(1)}
function fcat(c){FR.cat=c;FR.mine=false;route(1)}
function fmine(){FR.mine=!FR.mine;if(FR.mine)FR.cat="";route(1)}
function ftog(id){FR.open.has(id)?FR.open.delete(id):FR.open.add(id);forumRender()}
function flike(id){const p=FR.posts.find(x=>x.id==id);if(!p)return;const sw=()=>{p.liked=!p.liked;p.likes+=p.liked?1:-1};sw();forumRender();api("forum_like",{id}).then(r=>{if(!r.ok){sw();forumRender();toast(r.error||"Action impossible.")}})}
function fdel(id){if(!confirm("Supprimer cette publication ?"))return;FR.posts=FR.posts.filter(x=>x.id!=id);forumRender();api("forum_delete",{id})}
async function fcom(id){const i=$("#ci"+id);if(!i)return;const b=i.value.trim();if(!b)return;const r=await api("forum_comment",{post_id:id,body:b});if(!r.ok)return toast(r.error||"Commentaire impossible.");
 const p=FR.posts.find(x=>x.id==id);if(!p)return;p.comments.push({id:r.id,author:r.author,uid:prof().uid,av:prof().av,body:b,ts:Math.floor(Date.now()/1e3),del:true});i.value="";forumRender();const n=$("#ci"+id);if(n)n.focus()}
function fcdel(pid,cid){const p=FR.posts.find(x=>x.id==pid);if(!p)return;p.comments=p.comments.filter(c=>c.id!=cid);forumRender();api("forum_comment_delete",{id:cid})}
/* profil */
async function pSave(){const name=$("#pn").value.trim().slice(0,20),goal=$("#pg").value,r=await api("profile_save",{name,goal});if(!r.ok)return toast(r.error||"Enregistrement impossible.");Object.assign(prof(),{name,goal});toast("Profil enregistré")}
async function pAcct(){const r=await api("account_save",{email:$("#pe").value.trim(),current:$("#pw0").value,new:$("#pw1").value});if(!r.ok)return toast(r.error||"Mise à jour impossible.");const ch=!!$("#pw1").value;prof().email=r.email;$("#pw0").value="";$("#pw1").value="";toast(ch?"E-mail et mot de passe à jour":"Compte mis à jour")}
/* Vercel limite les envois à 4,5 Mo : la photo est recadrée en carré 256 px et compressée dans le navigateur avant l'envoi */
function cropAvatar(f){return new Promise(res=>{const u=URL.createObjectURL(f),im=new Image(),done=v=>{URL.revokeObjectURL(u);res(v)};
 im.onload=()=>{try{const w=im.naturalWidth,h=im.naturalHeight,s=Math.min(w,h),c=document.createElement("canvas");c.width=c.height=256;const x=c.getContext("2d");x.fillStyle="#0f1210";x.fillRect(0,0,256,256);x.drawImage(im,(w-s)/2,(h-s)/2,s,s,0,0,256,256);
  c.toBlob(b=>done(b?new File([b],"avatar.jpg",{type:"image/jpeg"}):f),"image/jpeg",.86)}catch(e){done(f)}};im.onerror=()=>done(f);im.src=u})}
async function pAvatar(inp){const f0=inp.files[0];inp.value="";if(!f0)return;if(f0.size>20*1048576)return toast("Photo trop lourde (20 Mo maximum).");const f=await cropAvatar(f0);if(f.size>UMAX)return toast("Photo trop lourde : choisis une image plus petite.");const fd=new FormData();fd.append("file",f);fd.append("kind","avatar");const r=await upl(fd);if(!r.ok)return toast(r.error||"Envoi impossible.");prof().av=r.ver;navAv();route(1);toast("Photo mise à jour")}
async function pAvDel(){const r=await api("avatar_delete");if(!r.ok)return toast(r.error||"Action impossible.");prof().av=0;navAv();route(1)}
/* accueil + visite guidée */
const prof=()=>DB.prof;
function welcome(){const w=document.createElement("div");w.id="wl";
 w.innerHTML=`<div class="box"><div class="lg"><svg class="fin" aria-hidden="true"><use href="#fin"/></svg>GYM <b>SQUALE</b></div><p class="mu" style="font-size:17px">Ton coach de musculation : crée tes programmes, suis tes séances, bats tes records.</p><div class="row" style="justify-content:center;margin:18px 0 8px">${["Programmes sur mesure","Séances guidées","Suivi & records"].map(t=>`<span class="tag ok">${t}</span>`).join("")}</div><div class="card" style="text-align:left"><label class="mu">Comment t’appelles-tu ?</label><input id="wn" placeholder="Ton prénom" maxlength="20" style="margin:6px 0 12px"><label class="mu">Ton objectif</label><select id="wg" style="margin-top:6px"><option>Prendre du muscle</option><option>Gagner en force</option><option>Perdre du poids</option><option>Améliorer mon endurance</option></select></div><button class="pri pulse" style="width:100%;margin-top:14px;padding:13px" onclick="wDone(1)">Commencer la visite guidée</button><button class="fav" style="margin-top:8px;color:var(--mu)" onclick="wDone(0)">Passer, je connais déjà</button></div>`;
 document.body.append(w);w.querySelectorAll(".box>*").forEach((c,i)=>c.style.setProperty("--k",i))}
function wDone(t){Object.assign(DB.prof,{name:$("#wn").value.trim().slice(0,20),goal:$("#wg").value});DB.seen=1;api("profile_save",{name:DB.prof.name,goal:DB.prof.goal});$("#wl").remove();if(t)tourStart();else route()}
const T=[["#/dash","dash","Ton dashboard","Tes stats, ton rang, ta série de jours et ton calendrier de régularité. Il se remplit dès ta première séance."],["#/pg","pg","Tes programmes","Crée ton propre programme jour par jour, génère-en un automatiquement ou importe le code d’un ami. Lance ensuite une séance guidée en un clic."],["#/muscles","muscles","Les muscles","Choisis un groupe musculaire pour voir ses exercices et ce que tu as déjà travaillé."],["#/ex","ex","Bibliothèque d’exercices","Recherche et filtre par muscle, matériel ou difficulté. Chaque fiche explique l’exécution et te permet d’enregistrer tes séries."],["#/hist","hist","Tes entraînements","Tout l’historique de tes séances, avec export CSV."],["#/prog","prog","Ta progression","Courbes de force estimée, niveaux par exercice et records battus."],["#/forum","forum","Le forum","Publie tes progrès (tu peux joindre une séance), pose des questions et encourage les autres membres avec des j’aime et des commentaires."],["#/pg","pg","À toi de jouer !","Le plus rapide : génère un programme, ajuste-le, enregistre-le puis appuie sur « Lancer ». Tu peux relancer cette visite via « Aide » dans le menu."]];
let ts=0;function tourStart(){ts=0;tour()}
function tour(){if(ts>=T.length||ts<0)return tourEnd();const s=T[ts];let t=$("#tour");if(!t){t=document.createElement("div");t.id="tour";document.body.append(t)}
 if(location.hash!=s[0])location.hash=s[0];
 document.querySelectorAll("nav a").forEach(a=>a.classList.toggle("hl",a.dataset.r==s[1]&&ts<T.length-1));
 const n=$('nav a[data-r="'+s[1]+'"]');if(n)n.scrollIntoView({block:"nearest",inline:"center"});
 const nm=prof().name;
 t.innerHTML=`<div class="dots">${T.map((_,i)=>`<i class="${i==ts?"on":""}"></i>`).join("")}</div><b style="font-size:17px;display:block;margin:8px 0 4px">${ts==0&&nm?"Salut "+esc(nm)+" ! ":""}${s[2]}</b><p class="mu" style="margin:0 0 12px">${s[3]}</p><div class="row" style="justify-content:space-between"><button class="fav" style="color:var(--mu)" onclick="tourEnd()">Passer</button><span class="row">${ts?'<button onclick="ts--;tour()">Précédent</button>':""}<button class="pri" onclick="ts++;tour()">${ts==T.length-1?"Terminer":"Suivant"}</button></span></div>`}
function tourEnd(){const t=$("#tour");if(t)t.remove();document.querySelectorAll("nav a.hl").forEach(a=>a.classList.remove("hl"))}
async function init(){const r=await api("bootstrap");
 if(!r.ok){app.innerHTML='<div class="card empty">Impossible de charger les données. Vérifie que MariaDB/MySQL est démarré.</div>';return}
 M=r.muscles;E=r.exercises;DB.logs=r.logs;DB.favs=r.favs;DB.progs=r.programs;DB.prof=r.profile;DB.seen=r.seen;buildOpt();navAv();
 addEventListener("hashchange",()=>route());route();if(!DB.seen)welcome()}
init();
