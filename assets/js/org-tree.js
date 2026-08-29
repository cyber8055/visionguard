// assets/js/org-tree.js

async function loadOrgTree() {
  const container = document.getElementById('org-tree-container');
  if (!container) return;

  container.innerHTML = `<div style="text-align:center; padding:40px; color:var(--vg-text-muted);"><i class='bx bx-loader-alt bx-spin' style="font-size:24px;"></i><br>Loading hierarchy...</div>`;
  
  try {
    const token = sessionStorage.getItem('vg_token');
    const res = await fetch('../php/api/org_tree.php', { headers: { 'Authorization': 'Bearer ' + token } });
    const json = await res.json();
    
    if (!json.success) {
      container.innerHTML = `<div style="color:var(--vg-status-danger); padding:20px;">Error: ${json.message}</div>`;
      return;
    }

    container.innerHTML = generateTreeHTML(json.data);
    injectUserModal();
  } catch (err) {
    container.innerHTML = `<div style="color:var(--vg-status-danger); padding:20px;">Failed to fetch organization tree.</div>`;
  }
}

function generateTreeHTML(nodes) {
  let html = '<div class="org-tree">';
  nodes.forEach(node => {
    html += renderNode(node);
  });
  html += '</div>';
  return html;
}

function renderNode(node) {
  const hasChildren = node.children && node.children.length > 0;
  const plantLabel = node.plant ? `<span style="font-size:12px; color:var(--vg-text-muted); margin-left:6px;">(${node.plant})</span>` : '';
  const emailLabel = node.email ? `<div style="font-size:13px; color:var(--vg-text-secondary); margin-top:2px;"><i class='bx bx-envelope' style="font-size:12px; margin-right:4px;"></i>${node.email}</div>` : '';
  
  const initials = node.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
  const safeNodeData = encodeURIComponent(JSON.stringify(node));

  const avatarHTML = `
    <div class="user-avatar" onclick="openUserModal('${safeNodeData}'); event.stopPropagation();" title="Click to view details" style="width: 36px; height: 36px; border-radius: 50%; background: var(--vg-accent-primary); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; color: #000; cursor: pointer; flex-shrink: 0; box-shadow: 0 0 10px rgba(0, 243, 255, 0.2); transition: transform 0.2s;">
      ${initials}
    </div>
  `;

  let html = `
    <div class="tree-node" style="margin-bottom: 8px;">
      <div class="tree-item" ${hasChildren ? 'onclick="toggleTreeNode(this)"' : ''} style="display:flex; align-items:center; background: rgba(255,255,255,0.02); padding: 12px; border-radius: 6px; cursor: ${hasChildren ? 'pointer' : 'default'}; border: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;">
        <div style="display:flex; align-items:center; gap:12px; flex: 1;">
          <div style="width: 20px; display:flex; justify-content:center;">
            ${hasChildren ? `<i class='bx bx-chevron-right' style="transition: transform 0.2s; color: var(--vg-text-muted); font-size: 18px;"></i>` : ''}
          </div>
          ${avatarHTML}
          <div style="display:flex; flex-direction:column;">
            <span style="font-size:15px; font-weight:600; color:var(--vg-text-primary); display:flex; align-items:center;">${node.name} ${plantLabel}</span>
            ${emailLabel}
          </div>
        </div>
      </div>
  `;

  if (hasChildren) {
    // We use a CSS max-height approach for smooth animations
    html += `<div class="tree-children" style="max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; padding-left: 32px; border-left: 1px dashed rgba(255,255,255,0.1); margin-left: 22px;">`;
    html += `<div style="padding-top: 6px;">`; // Inner wrapper for padding to not glitch animation
    node.children.forEach(child => {
      html += renderNode(child);
    });
    html += `</div></div>`;
  } else if (node.role !== 'Worker') {
     html += `<div class="tree-children" style="max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; padding-left: 32px; border-left: 1px dashed rgba(255,255,255,0.1); margin-left: 22px;"><div style="padding: 8px 0 8px 12px; font-size:12px; color:var(--vg-text-muted);">No subordinates assigned</div></div>`;
  }

  html += `</div>`;
  return html;
}

function toggleTreeNode(element) {
  const childrenContainer = element.nextElementSibling;
  if (!childrenContainer) return;
  const chevron = element.querySelector('.bx-chevron-right, .bx-chevron-down');
  
  if (childrenContainer.style.maxHeight && childrenContainer.style.maxHeight !== '0px') {
    childrenContainer.style.maxHeight = '0px';
    if(chevron) {
      chevron.classList.remove('bx-chevron-down');
      chevron.classList.add('bx-chevron-right');
    }
  } else {
    // Calculate full height to transition to
    const scrollHeight = childrenContainer.scrollHeight;
    childrenContainer.style.maxHeight = scrollHeight + 100 + "px"; // added extra buffer for deep nesting
    if(chevron) {
      chevron.classList.remove('bx-chevron-right');
      chevron.classList.add('bx-chevron-down');
    }
    
    // Also trigger parents to update their max-height if deeply nested
    let parentTree = childrenContainer.parentElement.closest('.tree-children');
    while(parentTree) {
       parentTree.style.maxHeight = parseInt(parentTree.style.maxHeight || 0) + scrollHeight + 100 + "px";
       parentTree = parentTree.parentElement.closest('.tree-children');
    }
  }
}

// Custom Modal Logic
function injectUserModal() {
  if (document.getElementById('org-user-modal')) return;
  
  const modalHTML = `
    <div id="org-user-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
      <div style="background:var(--vg-bg-surface); width:100%; max-width:400px; border-radius:12px; border:1px solid var(--vg-border); box-shadow:0 10px 30px rgba(0,0,0,0.5); overflow:hidden; animation: modalPop 0.3s ease-out forwards;">
        
        <div style="background: linear-gradient(135deg, var(--vg-bg-dark) 0%, rgba(0, 243, 255, 0.1) 100%); padding: 30px 20px; text-align: center; position: relative; border-bottom: 1px solid var(--vg-border);">
          <button onclick="closeUserModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; color:var(--vg-text-muted); cursor:pointer; font-size:20px; transition: color 0.2s;"><i class='bx bx-x'></i></button>
          
          <div id="modal-avatar" style="width: 80px; height: 80px; border-radius: 50%; background: var(--vg-accent-primary); margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; color: #000; box-shadow: 0 0 20px rgba(0, 243, 255, 0.3); border: 3px solid var(--vg-bg-surface);">
            --
          </div>
          <h2 id="modal-name" style="margin: 0 0 5px 0; font-size: 20px; font-family: var(--font-outfit); color: var(--vg-text-primary);">Name</h2>
          <div id="modal-role" style="font-size: 14px; color: var(--vg-text-secondary); display:flex; align-items:center; justify-content:center; gap:6px;">
            <i class='bx bx-id-card'></i> <span>Role</span>
          </div>
        </div>

        <div style="padding: 24px;">
          <div style="display:flex; flex-direction:column; gap:16px;">
            
            <div style="display:flex; align-items:center; gap:12px;">
              <div style="width:40px; height:40px; border-radius:8px; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; color:var(--vg-text-muted);">
                <i class='bx bx-envelope' style="font-size:20px;"></i>
              </div>
              <div>
                <div style="font-size:12px; color:var(--vg-text-muted); text-transform:uppercase; letter-spacing:0.5px;">Email Address</div>
                <div id="modal-email" style="font-size:14px; color:var(--vg-text-primary); font-weight:500;">email@example.com</div>
              </div>
            </div>

            <div style="display:flex; align-items:center; gap:12px;">
              <div style="width:40px; height:40px; border-radius:8px; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; color:var(--vg-text-muted);">
                <i class='bx bx-building-house' style="font-size:20px;"></i>
              </div>
              <div>
                <div style="font-size:12px; color:var(--vg-text-muted); text-transform:uppercase; letter-spacing:0.5px;">Assigned Plant</div>
                <div id="modal-plant" style="font-size:14px; color:var(--vg-text-primary); font-weight:500;">N/A</div>
              </div>
            </div>

            <div style="display:flex; align-items:center; gap:12px;">
              <div style="width:40px; height:40px; border-radius:8px; background:rgba(239, 68, 68, 0.1); display:flex; align-items:center; justify-content:center; color:#ef4444;">
                <i class='bx bx-donate-blood' style="font-size:20px;"></i>
              </div>
              <div>
                <div style="font-size:12px; color:var(--vg-text-muted); text-transform:uppercase; letter-spacing:0.5px;">Blood Group</div>
                <div id="modal-blood" style="font-size:15px; color:#ef4444; font-weight:700;">--</div>
              </div>
            </div>

            <div style="display:flex; align-items:center; gap:12px; margin-top: 8px; padding-top: 16px; border-top: 1px solid var(--vg-border);">
              <div style="width:40px; height:40px; border-radius:8px; background:rgba(16, 185, 129, 0.1); display:flex; align-items:center; justify-content:center; color:#10b981;">
                <i class='bx bx-shield-quarter' style="font-size:20px;"></i>
              </div>
              <div style="flex:1;">
                <div style="font-size:12px; color:var(--vg-text-muted); text-transform:uppercase; letter-spacing:0.5px;">Account Security</div>
                <div style="font-size:13px; color:#10b981; font-weight:600; display:flex; align-items:center; gap:4px;">
                  <i class='bx bx-check-shield'></i> Encrypted & Protected
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
    <style>
      @keyframes modalPop {
        0% { opacity: 0; transform: scale(0.95) translateY(10px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
      }
    </style>
  `;
  document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function openUserModal(nodeDataString) {
  const node = JSON.parse(decodeURIComponent(nodeDataString));
  const modal = document.getElementById('org-user-modal');
  if(!modal) return;
  
  const initials = node.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
  
  document.getElementById('modal-avatar').innerText = initials;
  document.getElementById('modal-name').innerText = node.name;
  document.getElementById('modal-role').innerHTML = `<i class='bx bx-id-card'></i> <span>${node.role}</span>`;
  document.getElementById('modal-email').innerText = node.email || 'N/A';
  document.getElementById('modal-plant').innerText = node.plant || 'Global';
  document.getElementById('modal-blood').innerText = node.blood_group || 'Unknown';
  
  modal.style.display = 'flex';
}

function closeUserModal() {
  const modal = document.getElementById('org-user-modal');
  if(modal) modal.style.display = 'none';
}
