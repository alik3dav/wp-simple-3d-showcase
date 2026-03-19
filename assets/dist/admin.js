import{G as L,B as I,V as w}from"./chunks/GLTFLoader-DJvXrjqG.js";const q=new L,g=new Map;var P;const i=((P=window.wp3dsAdminConfig)==null?void 0:P.i18n)??{};function b(t){return Number.parseFloat(t).toFixed(3)}function o(t){return String(t).replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;")}function M(t,a){const e=[];let r=t;for(;r;){const n=r.name||r.type||"Node";e.unshift(n),r=r.parent&&r.parent.type!=="Scene"?r.parent:null}return`${e.join(" / ")}#${a}`}function N(t){if(!t)return[];try{const a=JSON.parse(t);return Array.isArray(a)?a.filter(e=>e&&e.key).map(e=>({key:String(e.key),name:String(e.name||i.part||"Part"),description:String(e.description||""),characteristics:String(e.characteristics||""),x:Number.parseFloat(e.x||0)||0,y:Number.parseFloat(e.y||0)||0,z:Number.parseFloat(e.z||0)||0})):[]}catch{return[]}}function f(t,a){const e=t.querySelector("#wp3ds_explode_parts");e&&(e.value=JSON.stringify(a))}function $(t){return t?(i.partsDetected||"Detected %d parts automatically.").replace("%d",String(t)):i.noPartsDetected||"No mesh parts were detected in this GLB file."}function C(t,a){const e=t.querySelector("[data-parts-list]"),r=t.querySelector("[data-parts-status]");if(!(!e||!r)){if(!a.length){e.hidden=!0,e.innerHTML="",r.textContent=$(0),f(t,[]);return}r.textContent=$(a.length),e.hidden=!1,e.innerHTML=`
    <table class="widefat striped wp3ds-parts-table">
      <thead>
        <tr>
          <th>${o(i.partColumn||"Part")}</th>
          <th>${o(i.descriptionColumn||"Description")}</th>
          <th>${o(i.characteristicsCol||"Characteristics")}</th>
          <th>X</th>
          <th>Y</th>
          <th>Z</th>
        </tr>
      </thead>
      <tbody>
        ${a.map((n,s)=>`
          <tr>
            <td>
              <label>
                <span class="screen-reader-text">${o(i.displayName||"Display name")}</span>
                <input type="text" class="regular-text" data-text-input="name" data-index="${s}" value="${o(n.name)}" placeholder="${o(i.displayName||"Display name")}">
              </label>
              <div class="description">${o(n.key)}</div>
            </td>
            <td>
              <textarea rows="3" class="large-text" data-text-input="description" data-index="${s}" placeholder="${o(i.shortSummary||"Short summary shown in the viewer")}">${o(n.description)}</textarea>
            </td>
            <td>
              <textarea rows="3" class="large-text" data-text-input="characteristics" data-index="${s}" placeholder="${o(i.onePerLine||"One characteristic per line")}">${o(n.characteristics)}</textarea>
            </td>
            <td><input type="number" step="0.001" class="small-text" data-axis-input="x" data-index="${s}" value="${b(n.x)}"></td>
            <td><input type="number" step="0.001" class="small-text" data-axis-input="y" data-index="${s}" value="${b(n.y)}"></td>
            <td><input type="number" step="0.001" class="small-text" data-axis-input="z" data-index="${s}" value="${b(n.z)}"></td>
          </tr>
        `).join("")}
      </tbody>
    </table>
  `,f(t,a)}}function v(t,a){const e=a.querySelector("[data-parts-status]"),r=a.querySelector("[data-parts-list]"),n=N(a.dataset.explodeParts||"[]"),s=new Map(n.map(d=>[d.key,d]));if(!t){r&&(r.hidden=!0,r.innerHTML=""),e&&(e.textContent=i.selectGlbPrompt||"Select a GLB file to detect model parts."),f(a,n);return}e&&(e.textContent=i.detectingParts||"Detecting mesh parts from the GLB file…"),q.load(t,d=>{const c=d.scene,x=new I().setFromObject(c).getCenter(new w),p=[];let y=0;c.traverse(h=>{if(!h.isMesh)return;y+=1;const S=M(h,y),F=h.name||`${i.part||"Part"} ${y}`,m=h.getWorldPosition(new w).clone().sub(x);m.lengthSq()===0?m.set(0,1,0):m.normalize();const l=s.get(S);p.push({key:S,name:l?l.name:F,description:l?l.description:"",characteristics:l?l.characteristics:"",x:l?l.x:Number.parseFloat(m.x.toFixed(3)),y:l?l.y:Number.parseFloat(m.y.toFixed(3)),z:l?l.z:Number.parseFloat(m.z.toFixed(3))})}),a.dataset.explodeParts=JSON.stringify(p),C(a,p)},void 0,()=>{r&&(r.hidden=!0,r.innerHTML=""),e&&(e.textContent=i.loadGlbError||"Unable to inspect the selected GLB file.")})}function E(t){const a=document.querySelector(t.dataset.clearMedia||""),e=document.querySelector(t.dataset.clearMediaId||"");a&&(a.value="",a.dispatchEvent(new Event("change",{bubbles:!0}))),e&&(e.value="")}function T(t,a){return a?String((t==null?void 0:t.url)||"").toLowerCase().endsWith(`.${a.toLowerCase()}`):!0}function z(){document.addEventListener("click",t=>{const a=t.target.closest("[data-clear-media]");if(a){t.preventDefault(),E(a);return}const e=t.target.closest("[data-media-target]");if(!e)return;t.preventDefault();const r=e.dataset.mediaTarget,n=document.querySelector(r),s=document.querySelector(e.dataset.mediaIdTarget||"");if(!r||!n)return;const d=`${r}:${e.dataset.mediaTitle||""}`;if(g.has(d)){g.get(d).open();return}const c=wp.media({title:e.dataset.mediaTitle||i.selectFile||"Select file",button:{text:e.dataset.mediaButton||i.useFile||"Use this file"},multiple:!1});c.on("select",()=>{const u=c.state().get("selection").first().toJSON();if(!T(u,e.dataset.allowedExtension)){window.alert(i.invalidFileType||"Please select a file with the required extension.");return}n.value=u.url||"",s&&(s.value=u.id||""),n.dispatchEvent(new Event("change",{bubbles:!0}))}),g.set(d,c),c.open()})}function A(){const t=document.querySelector(".wp3ds-explode-parts"),a=document.querySelector("#wp3ds_model_url");!t||!a||(a.addEventListener("change",()=>{v(a.value.trim(),t)}),t.addEventListener("input",e=>{var u;const r=e.target.closest("[data-axis-input]"),n=e.target.closest("[data-text-input]"),s=N(((u=t.querySelector("#wp3ds_explode_parts"))==null?void 0:u.value)||"[]");if(r){const x=Number.parseInt(r.dataset.index||"-1",10),p=r.dataset.axisInput;if(!s[x]||!["x","y","z"].includes(p))return;s[x][p]=Number.parseFloat(r.value||"0")||0,t.dataset.explodeParts=JSON.stringify(s),f(t,s);return}if(!n)return;const d=Number.parseInt(n.dataset.index||"-1",10),c=n.dataset.textInput;!s[d]||!["name","description","characteristics"].includes(c)||(s[d][c]=n.value,t.dataset.explodeParts=JSON.stringify(s),f(t,s))}),v(a.value.trim(),t))}document.addEventListener("DOMContentLoaded",()=>{z(),A()});
