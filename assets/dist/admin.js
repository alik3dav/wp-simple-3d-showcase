import{G as L,B as F,V as w}from"./chunks/GLTFLoader-DJvXrjqG.js";const q=new L,g=new Map;var P;const i=((P=window.wp3dsAdminConfig)==null?void 0:P.i18n)??{};function b(e){return Number.parseFloat(e).toFixed(3)}function o(e){return String(e).replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;")}function T(e,a){const t=[];let r=e;for(;r;){const n=r.name||r.type||"Node";t.unshift(n),r=r.parent&&r.parent.type!=="Scene"?r.parent:null}return`${t.join(" / ")}#${a}`}function I(e){if(!e)return[];try{const a=JSON.parse(e);return Array.isArray(a)?a.filter(t=>t&&t.key).map(t=>({key:String(t.key),name:String(t.name||i.part||"Part"),description:String(t.description||""),characteristics:String(t.characteristics||""),x:Number.parseFloat(t.x||0)||0,y:Number.parseFloat(t.y||0)||0,z:Number.parseFloat(t.z||0)||0})):[]}catch{return[]}}function f(e,a){const t=e.querySelector("#wp3ds_explode_parts");t&&(t.value=JSON.stringify(a))}function $(e){return e?(i.partsDetected||"Detected %d parts automatically.").replace("%d",String(e)):i.noPartsDetected||"No mesh parts were detected in this GLB file."}function E(e,a){const t=e.querySelector("[data-parts-list]"),r=e.querySelector("[data-parts-status]");if(!(!t||!r)){if(!a.length){t.hidden=!0,t.innerHTML="",r.textContent=$(0),f(e,[]);return}r.textContent=$(a.length),t.hidden=!1,t.innerHTML=`
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
  `,f(e,a)}}function v(e,a){const t=a.querySelector("[data-parts-status]"),r=a.querySelector("[data-parts-list]"),n=I(a.dataset.explodeParts||"[]"),s=new Map(n.map(l=>[l.key,l]));if(!e){r&&(r.hidden=!0,r.innerHTML=""),t&&(t.textContent=i.selectGlbPrompt||"Select a GLB file to detect model parts."),f(a,n);return}t&&(t.textContent=i.detectingParts||"Detecting mesh parts from the GLB file…"),q.load(e,l=>{const c=l.scene,x=new F().setFromObject(c).getCenter(new w),p=[];let y=0;c.traverse(h=>{if(!h.isMesh)return;y+=1;const S=T(h,y),N=h.name||`${i.part||"Part"} ${y}`,m=h.getWorldPosition(new w).clone().sub(x);m.lengthSq()===0?m.set(0,1,0):m.normalize();const d=s.get(S);p.push({key:S,name:d?d.name:N,description:d?d.description:"",characteristics:d?d.characteristics:"",x:d?d.x:Number.parseFloat(m.x.toFixed(3)),y:d?d.y:Number.parseFloat(m.y.toFixed(3)),z:d?d.z:Number.parseFloat(m.z.toFixed(3))})}),a.dataset.explodeParts=JSON.stringify(p),E(a,p)},void 0,()=>{r&&(r.hidden=!0,r.innerHTML=""),t&&(t.textContent=i.loadGlbError||"Unable to inspect the selected GLB file.")})}function M(e){const a=document.querySelector(e.dataset.clearMedia||""),t=document.querySelector(e.dataset.clearMediaId||"");a&&(a.value="",a.dispatchEvent(new Event("change",{bubbles:!0}))),t&&(t.value="")}function C(e,a){return a?String((e==null?void 0:e.url)||"").toLowerCase().endsWith(`.${a.toLowerCase()}`):!0}function z(){document.addEventListener("input",e=>{const a=e.target.closest("[data-media-url-input]");if(!a)return;const t=document.querySelector(a.dataset.mediaIdTarget||"");t&&(t.value="")}),document.addEventListener("click",e=>{const a=e.target.closest("[data-clear-media]");if(a){e.preventDefault(),M(a);return}const t=e.target.closest("[data-media-target]");if(!t)return;e.preventDefault();const r=t.dataset.mediaTarget,n=document.querySelector(r),s=document.querySelector(t.dataset.mediaIdTarget||"");if(n&&!n.dataset.mediaIdTarget&&t.dataset.mediaIdTarget&&(n.dataset.mediaIdTarget=t.dataset.mediaIdTarget),!r||!n)return;const l=`${r}:${t.dataset.mediaTitle||""}`;if(g.has(l)){g.get(l).open();return}const c=wp.media({title:t.dataset.mediaTitle||i.selectFile||"Select file",button:{text:t.dataset.mediaButton||i.useFile||"Use this file"},multiple:!1});c.on("select",()=>{const u=c.state().get("selection").first().toJSON();if(!C(u,t.dataset.allowedExtension)){window.alert(i.invalidFileType||"Please select a file with the required extension.");return}n.value=u.url||"",s&&(s.value=u.id||""),n.dispatchEvent(new Event("change",{bubbles:!0}))}),g.set(l,c),c.open()})}function A(){const e=document.querySelector(".wp3ds-explode-parts"),a=document.querySelector("#wp3ds_model_url");!e||!a||(a.addEventListener("change",()=>{v(a.value.trim(),e)}),e.addEventListener("input",t=>{var u;const r=t.target.closest("[data-axis-input]"),n=t.target.closest("[data-text-input]"),s=I(((u=e.querySelector("#wp3ds_explode_parts"))==null?void 0:u.value)||"[]");if(r){const x=Number.parseInt(r.dataset.index||"-1",10),p=r.dataset.axisInput;if(!s[x]||!["x","y","z"].includes(p))return;s[x][p]=Number.parseFloat(r.value||"0")||0,e.dataset.explodeParts=JSON.stringify(s),f(e,s);return}if(!n)return;const l=Number.parseInt(n.dataset.index||"-1",10),c=n.dataset.textInput;!s[l]||!["name","description","characteristics"].includes(c)||(s[l][c]=n.value,e.dataset.explodeParts=JSON.stringify(s),f(e,s))}),v(a.value.trim(),e))}document.addEventListener("DOMContentLoaded",()=>{z(),A()});
