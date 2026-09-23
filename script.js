const ACCOUNTS_API = "API/accounts.php";
const SKILLS_API   = "API/skills.php";
const OTP_API      = "API/verify_otp.php";
const IOT_API      = "API/iot_data.php";

function escapeHtml(str) { return String(str).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;"); }

function flagFieldError(target, message) {
    target.focus();
    target.style.borderColor = "#ff3c3c";
    const old = target.placeholder;
    target.placeholder = message;
    setTimeout(() => { target.style.borderColor = ""; target.placeholder = old; }, 2500);
}

function updateDateTime() { const el = document.getElementById("datetime"); if (el) el.textContent = new Date().toLocaleString(); }

function updateMajors() {
    const course      = document.getElementById("courseSelect").value;
    const majorSelect = document.getElementById("majorSelect");
    majorSelect.innerHTML = '<option value="">Select Major</option>';
    if (course === "BSIT") {
        ["Network Security", "Web Development"].forEach(function (m) {
            const opt = document.createElement("option");
            opt.value = m; opt.text = m;
            majorSelect.appendChild(opt);
        });
    }
}

document.addEventListener("change", function () {
    const course = document.getElementById("courseSelect").value;
    const major  = document.getElementById("majorSelect").value;
    const result = document.getElementById("selectionResult");
    if (result && course && major) { result.textContent = `Selected Course: ${course} | Selected Major: ${major}`; }
});

function showPopup() { window.open("https://isu.edu.ph/", "ISU Website", "width=1280,height=800"); }

const levelWidth = { Beginner: "15%", Basic: "30%", Intermediate: "55%", Advanced: "75%", Expert: "95%" };

let skillsCache          = [];
let editingSkillId       = null;
let pendingDeleteSkillId = null;

async function refreshSkills() {
    try {
        const res  = await fetch(`${SKILLS_API}?action=list`);
        const data = await res.json();
        if (data.success) { skillsCache = data.skills; renderSkillsTable(skillsCache); renderSkillsDisplay(skillsCache); }
        else { console.error("Failed to load skills:", data.message); }
    } catch (err) { console.error("Network error loading skills:", err); }
}

function renderSkillsDisplay(list) {
    const container  = document.getElementById("skillsDisplayContainer");
    const countBadge = document.getElementById("skillsDisplayCount");
    if (!container) return;
    if (countBadge) countBadge.textContent = `(${list.length})`;
    if (list.length === 0) { container.innerHTML = '<p class="crud-empty">No skills added yet.</p>'; return; }
    const groups = {};
    list.forEach(s => { if (!groups[s.category]) groups[s.category] = []; groups[s.category].push(s); });
    container.innerHTML = Object.entries(groups).map(([cat, skills]) => `
        <div class="sub-head">${escapeHtml(cat)}</div>
        <div class="skill-rows">
            ${skills.map(s => {
                const w = levelWidth[s.level] || "20%";
                return `<div class="skill-row">
                    <span class="skill-name">${escapeHtml(s.name)}</span>
                    <div class="skill-bar"><div class="skill-fill" style="width:${w};"></div></div>
                    <span class="skill-level">${escapeHtml(s.level)}</span>
                </div>`;
            }).join("")}
        </div>`).join("");
}

function renderSkillsTable(list) {
    const tbody      = document.getElementById("skillsTableBody");
    const noMsg      = document.getElementById("noSkillsMsg");
    const table      = document.getElementById("skillsTable");
    const countBadge = document.getElementById("skillCount");
    if (!tbody) return;
    tbody.innerHTML = "";
    if (countBadge) countBadge.textContent = `(${list.length})`;
    if (list.length === 0) { noMsg.style.display = "block"; table.style.display = "none"; return; }
    noMsg.style.display = "none"; table.style.display = "table";
    list.forEach(function (skill, index) {
        const width = levelWidth[skill.level] || "20%";
        const row   = document.createElement("tr");
        row.id = `row-${skill.id}`;
        row.innerHTML = `
            <td style="color:var(--text-muted);font-family:var(--font-mono);font-size:12px;">${index + 1}</td>
            <td>${escapeHtml(skill.name)}</td>
            <td><span class="badge">${escapeHtml(skill.category)}</span></td>
            <td><span class="skill-level">${escapeHtml(skill.level)}</span></td>
            <td style="min-width:120px;">
                <div class="skill-bar" style="flex:unset;width:100%;">
                    <div class="skill-fill" style="width:${width};"></div>
                </div>
            </td>
            <td class="crud-actions-cell">
                <button class="crud-btn crud-btn-edit"   onclick="editSkill(${skill.id})">✎ Edit</button>
                <button class="crud-btn crud-btn-delete" onclick="openDeleteModal(${skill.id})">✕ Delete</button>
            </td>`;
        tbody.appendChild(row);
    });
}

async function submitSkill() {
    const nameInput = document.getElementById("skillNameInput");
    const catInput  = document.getElementById("skillCategoryInput");
    const lvlInput  = document.getElementById("skillLevelInput");
    const name      = nameInput.value.trim();
    const category  = catInput.value;
    const level     = lvlInput.value;
    if (!name) { flagFieldError(nameInput, "⚠ Skill name is required!"); return; }
    try {
        const formData = new URLSearchParams();
        formData.set("action",   editingSkillId !== null ? "update" : "create");
        if (editingSkillId !== null) formData.set("id", editingSkillId);
        formData.set("name",     name);
        formData.set("category", category);
        formData.set("level",    level);
        const res  = await fetch(SKILLS_API, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: formData.toString() });
        const data = await res.json();
        if (!data.success) { flagFieldError(nameInput, "⚠ " + data.message); return; }
        cancelEdit();
        await refreshSkills();
    } catch (err) { console.error("Network error saving skill:", err); flagFieldError(nameInput, "⚠ Server unreachable — is Apache/MySQL running?"); }
}

function editSkill(id) {
    const skill = skillsCache.find(s => s.id == id);
    if (!skill) return;
    editingSkillId = id;
    document.getElementById("skillNameInput").value     = skill.name;
    document.getElementById("skillCategoryInput").value = skill.category;
    document.getElementById("skillLevelInput").value    = skill.level;
    document.getElementById("formTitle").textContent       = "Edit Skill";
    document.getElementById("submitSkillBtn").textContent  = "✔ Save Changes";
    document.getElementById("cancelEditBtn").style.display = "inline-block";
    document.querySelector(".crud-form-card").scrollIntoView({ behavior: "smooth", block: "center" });
}

function cancelEdit() {
    editingSkillId = null;
    document.getElementById("skillNameInput").value        = "";
    document.getElementById("skillCategoryInput").value    = "Networking & Security";
    document.getElementById("skillLevelInput").value       = "Basic";
    document.getElementById("formTitle").textContent       = "Add New Skill";
    document.getElementById("submitSkillBtn").textContent  = "+ Add Skill";
    document.getElementById("cancelEditBtn").style.display = "none";
}

function openDeleteModal(id) {
    const skill = skillsCache.find(s => s.id == id);
    if (!skill) return;
    pendingDeleteSkillId = id;
    document.getElementById("deleteModalMsg").innerHTML =
        `Are you sure you want to delete <strong>"${escapeHtml(skill.name)}"</strong>?<br>This action cannot be undone.`;
    document.getElementById("confirmDeleteBtn").onclick = function () { deleteSkill(pendingDeleteSkillId); closeDeleteModal(); };
    document.getElementById("deleteModal").style.display = "flex";
}

function closeDeleteModal() { pendingDeleteSkillId = null; document.getElementById("deleteModal").style.display = "none"; }

document.addEventListener("click", function (e) {
    const modal = document.getElementById("deleteModal");
    if (e.target === modal) closeDeleteModal();
});

async function deleteSkill(id) {
    try {
        const formData = new URLSearchParams();
        formData.set("action", "delete");
        formData.set("id", id);
        const res  = await fetch(SKILLS_API, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: formData.toString() });
        const data = await res.json();
        if (!data.success) { console.error("Delete skill failed:", data.message); return; }
        if (editingSkillId == id) cancelEdit();
        await refreshSkills();
    } catch (err) { console.error("Network error deleting skill:", err); }
}


// ============================================================
//  ACCOUNTS CRUD
// ============================================================

let accountsCache          = [];
let editingAccountId       = null;
let pendingDeleteAccountId = null;
let currentLoggedInUser    = null;

async function refreshAccounts() {
    try {
        const res  = await fetch(`${ACCOUNTS_API}?action=list`);
        const data = await res.json();
        if (data.success) { accountsCache = data.accounts; renderAccountsTable(accountsCache); }
        else { console.error("Failed to load accounts:", data.message); }
    } catch (err) { console.error("Network error loading accounts:", err); }
}

function renderAccountsTable(list) {
    const tbody      = document.getElementById("accountsTableBody");
    const noMsg      = document.getElementById("noAccountsMsg");
    const table      = document.getElementById("accountsTable");
    const countBadge = document.getElementById("accountCount");
    if (!tbody) return;
    tbody.innerHTML = "";
    if (countBadge) countBadge.textContent = `(${list.length})`;
    if (list.length === 0) { noMsg.style.display = "block"; table.style.display = "none"; renderViewAccountsTable(list); return; }
    noMsg.style.display = "none"; table.style.display = "table";
    list.forEach(function (account, index) {
        const row = document.createElement("tr");
        row.id = `account-row-${account.id}`;
        const registered = account.created_at ? new Date(account.created_at.replace(" ", "T")).toLocaleDateString() : "—";
        const isMe = currentLoggedInUser && currentLoggedInUser.id == account.id;
        const fullName = [account.first_name, account.middle_name, account.last_name].filter(Boolean).join(" ");
        row.innerHTML = `
            <td style="color:var(--text-muted);font-family:var(--font-mono);font-size:12px;">${index + 1}</td>
            <td>${escapeHtml(fullName)}</td>
            <td>${escapeHtml(account.username)}${isMe ? ' <span class="badge">You</span>' : ''}</td>
            <td style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);">${escapeHtml(account.email || "—")}</td>
            <td style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);">${registered}</td>
            <td class="crud-actions-cell">
                <button class="crud-btn crud-btn-edit"   onclick="editAccount(${account.id})">✎ Edit</button>
                <button class="crud-btn crud-btn-delete" onclick="openAccountDeleteModal(${account.id})"
                        ${isMe ? 'disabled title="You can\'t delete the account you\'re currently logged in as." style="opacity:0.4;cursor:not-allowed;"' : ''}>✕ Delete</button>
            </td>`;
        tbody.appendChild(row);
    });
    renderViewAccountsTable(list);
}

function renderViewAccountsTable(list) {
    const tbody      = document.getElementById("viewAccountsTableBody");
    const noMsg      = document.getElementById("noViewAccountsMsg");
    const table      = document.getElementById("viewAccountsTable");
    const countBadge = document.getElementById("viewAccountCount");
    if (!tbody) return;
    tbody.innerHTML = "";
    if (countBadge) countBadge.textContent = `(${list.length})`;
    if (list.length === 0) { if (noMsg) noMsg.style.display = "block"; if (table) table.style.display = "none"; return; }
    if (noMsg) noMsg.style.display = "none"; if (table) table.style.display = "table";
    list.forEach(function (account, index) {
        const row = document.createElement("tr");
        const registered = account.created_at ? new Date(account.created_at.replace(" ", "T")).toLocaleDateString() : "—";
        const isAdmin = account.role === "admin";
        row.innerHTML = `
            <td style="color:var(--text-muted);font-family:var(--font-mono);font-size:12px;">${index + 1}</td>
            <td>${escapeHtml(account.first_name || "")}</td>
            <td style="color:var(--text-muted);">${escapeHtml(account.middle_name || "—")}</td>
            <td>${escapeHtml(account.last_name || "")}</td>
            <td><span style="color:var(--accent-cyan);font-family:var(--font-mono);font-size:13px;">${escapeHtml(account.username)}</span></td>
            <td style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);">${escapeHtml(account.email || "—")}</td>
            <td><span class="badge" style="${isAdmin ? '' : 'color:var(--text-muted);border-color:var(--border-dim);background:transparent;'}">${escapeHtml(account.role || "viewer")}</span></td>
            <td style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);">${registered}</td>`;
        tbody.appendChild(row);
    });
}

async function submitAccount() {
    const firstNameInput  = document.getElementById("accountFirstNameInput");
    const middleNameInput = document.getElementById("accountMiddleNameInput");
    const lastNameInput   = document.getElementById("accountLastNameInput");
    const usernameInput   = document.getElementById("accountUsernameInput");
    const emailInput      = document.getElementById("accountEmailInput");
    const passwordInput   = document.getElementById("accountPasswordInput");

    const firstName  = firstNameInput.value.trim();
    const middleName = middleNameInput.value.trim();
    const lastName   = lastNameInput.value.trim();
    const username   = usernameInput.value.trim();
    const email      = emailInput.value.trim();
    const password   = passwordInput.value;
    const isEditing  = editingAccountId !== null;

    if (!firstName) { flagFieldError(firstNameInput, "⚠ First name is required!"); return; }
    if (!lastName)  { flagFieldError(lastNameInput,  "⚠ Surname is required!"); return; }
    if (!username)  { flagFieldError(usernameInput,  "⚠ Username is required!"); return; }
    if (!isEditing && !password) { flagFieldError(passwordInput, "⚠ Password is required!"); return; }

    try {
        const formData = new URLSearchParams();
        formData.set("action",      isEditing ? "update" : "create");
        if (isEditing) formData.set("id", editingAccountId);
        formData.set("first_name",  firstName);
        formData.set("middle_name", middleName);
        formData.set("last_name",   lastName);
        formData.set("username",    username);
        formData.set("email",       email);
        formData.set("password",    password);

        const res  = await fetch(ACCOUNTS_API, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: formData.toString() });
        const data = await res.json();
        if (!data.success) { flagFieldError(usernameInput, "⚠ " + data.message); return; }

        if (isEditing && currentLoggedInUser && currentLoggedInUser.id == editingAccountId) {
            currentLoggedInUser.username = username;
            document.getElementById("topbarUserLabel").textContent = `Logged in as ${username}`;
        }
        cancelAccountEdit();
        await refreshAccounts();
    } catch (err) { console.error("Network error saving account:", err); flagFieldError(usernameInput, "⚠ Server unreachable — is Apache/MySQL running?"); }
}

function editAccount(id) {
    const account = accountsCache.find(a => a.id == id);
    if (!account) return;
    editingAccountId = id;
    document.getElementById("accountFirstNameInput").value  = account.first_name  || "";
    document.getElementById("accountMiddleNameInput").value = account.middle_name || "";
    document.getElementById("accountLastNameInput").value   = account.last_name   || "";
    document.getElementById("accountUsernameInput").value   = account.username;
    document.getElementById("accountEmailInput").value      = account.email || "";
    const passwordInput = document.getElementById("accountPasswordInput");
    passwordInput.value = "";
    passwordInput.placeholder = "Leave blank to keep current password";
    document.getElementById("accountFormTitle").textContent       = "Edit Account";
    document.getElementById("submitAccountBtn").textContent       = "✔ Save Changes";
    document.getElementById("cancelAccountEditBtn").style.display = "inline-block";
    document.getElementById("accountFormCard").scrollIntoView({ behavior: "smooth", block: "center" });
}

function cancelAccountEdit() {
    editingAccountId = null;
    document.getElementById("accountFirstNameInput").value  = "";
    document.getElementById("accountMiddleNameInput").value = "";
    document.getElementById("accountLastNameInput").value   = "";
    document.getElementById("accountUsernameInput").value   = "";
    document.getElementById("accountEmailInput").value      = "";
    document.getElementById("accountPasswordInput").value   = "";
    document.getElementById("accountPasswordInput").placeholder = "Enter a password";
    document.getElementById("accountFormTitle").textContent       = "Register New Account";
    document.getElementById("submitAccountBtn").textContent       = "+ Register";
    document.getElementById("cancelAccountEditBtn").style.display = "none";
}

function openAccountDeleteModal(id) {
    const account = accountsCache.find(a => a.id == id);
    if (!account) return;
    pendingDeleteAccountId = id;

    const isSelf = currentLoggedInUser && currentLoggedInUser.id == id;
    const confirmBtn = document.getElementById("confirmAccountDeleteBtn");

    if (isSelf) {
        document.getElementById("accountDeleteModalMsg").innerHTML =
            `You can't delete <strong>"${escapeHtml(account.username)}"</strong> — that's the account you're currently logged in as.<br>
             Log in as a different admin first if you need to remove this account.`;
        confirmBtn.disabled = true;
        confirmBtn.style.opacity = "0.45";
        confirmBtn.style.cursor = "not-allowed";
        confirmBtn.onclick = null;
    } else {
        document.getElementById("accountDeleteModalMsg").innerHTML =
            `Are you sure you want to delete <strong>"${escapeHtml(account.username)}"</strong>?<br>This action cannot be undone.`;
        confirmBtn.disabled = false;
        confirmBtn.style.opacity = "";
        confirmBtn.style.cursor = "";
        confirmBtn.onclick = function () { deleteAccount(pendingDeleteAccountId); closeAccountDeleteModal(); };
    }

    document.getElementById("accountDeleteModal").style.display = "flex";
}

function closeAccountDeleteModal() { pendingDeleteAccountId = null; document.getElementById("accountDeleteModal").style.display = "none"; }

document.addEventListener("click", function (e) {
    const modal = document.getElementById("accountDeleteModal");
    if (e.target === modal) closeAccountDeleteModal();
});

async function deleteAccount(id) {
    try {
        const formData = new URLSearchParams();
        formData.set("action", "delete");
        formData.set("id", id);
        const res  = await fetch(ACCOUNTS_API, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: formData.toString() });
        const data = await res.json();
        if (!data.success) {
            alert(data.message || "Could not delete this account.");
            return;
        }
        if (currentLoggedInUser && currentLoggedInUser.id == id) handleLogout();
        if (editingAccountId == id) cancelAccountEdit();
        await refreshAccounts();
    } catch (err) {
        alert("Network error — could not delete this account. Is the server running?");
    }
}


// ============================================================
//  LOGIN GATE  —  tab switching
// ============================================================

function showGateTab(tab) {
    const loginPanel    = document.getElementById("gateLoginPanel");
    const registerPanel = document.getElementById("gateRegisterPanel");
    const otpPanel      = document.getElementById("gateOtpPanel");
    const tabs          = document.getElementById("gateTabs");
    const loginBtn      = document.getElementById("gateTabLoginBtn");
    const registerBtn   = document.getElementById("gateTabRegisterBtn");

    // Always hide OTP panel and show tabs when switching to login/register
    otpPanel.style.display  = "none";
    tabs.style.display      = "flex";

    if (tab === "register") {
        loginPanel.style.display    = "none";
        registerPanel.style.display = "flex";
        loginBtn.classList.remove("active");
        registerBtn.classList.add("active");
    } else {
        loginPanel.style.display    = "flex";
        registerPanel.style.display = "none";
        registerBtn.classList.remove("active");
        loginBtn.classList.add("active");
    }
    setGateMessage("gateLoginResult",    "", "");
    setGateMessage("gateRegisterResult", "", "");
    setGateMessage("gateOtpResult",      "", "");
}

function showOtpPanel(maskedEmail) {
    document.getElementById("gateLoginPanel").style.display    = "none";
    document.getElementById("gateRegisterPanel").style.display = "none";
    document.getElementById("gateTabs").style.display          = "none";
    document.getElementById("gateOtpPanel").style.display      = "flex";
    document.getElementById("otpMaskedEmail").textContent      = maskedEmail;
    document.getElementById("gateOtpInput").value              = "";
    setGateMessage("gateOtpResult", "", "");
    startResendCooldown();
    document.getElementById("gateOtpInput").focus();
}

function setGateMessage(id, message, type) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = message;
    el.className   = "login-result " + (type === "success" ? "login-success" : type === "error" ? "login-error" : "");
}


// ============================================================
//  LOGIN  —  Step 1: password  →  triggers OTP email
// ============================================================

async function handleGateLogin() {
    const usernameInput = document.getElementById("gateLoginUsername");
    const passwordInput = document.getElementById("gateLoginPassword");
    const username = usernameInput.value.trim();
    const password = passwordInput.value;

    if (!username || !password) {
        setGateMessage("gateLoginResult", "⚠ Please enter both username and password.", "error");
        return;
    }

    setGateMessage("gateLoginResult", "⏳ Verifying…", "");
    try {
        const formData = new URLSearchParams();
        formData.set("action",   "login");
        formData.set("username", username);
        formData.set("password", password);

        const res  = await fetch(ACCOUNTS_API, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: formData.toString() });
        const data = await res.json();

        if (!data.success) {
            setGateMessage("gateLoginResult", "✕ " + data.message, "error");
            return;
        }

        // data.otp_pending === true  →  show OTP panel
        usernameInput.value = "";
        passwordInput.value = "";
        showOtpPanel(data.masked_email || "your email");

    } catch (err) {
        setGateMessage("gateLoginResult", "⚠ Server unreachable — is Apache/MySQL running?", "error");
    }
}


// ============================================================
//  LOGIN  —  Step 2: OTP verification
// ============================================================

async function handleOtpVerify() {
    const otpInput = document.getElementById("gateOtpInput");
    const code     = otpInput.value.trim();

    if (code.length !== 6 || !/^\d{6}$/.test(code)) {
        setGateMessage("gateOtpResult", "⚠ Please enter the 6-digit code from your email.", "error");
        otpInput.focus();
        return;
    }

    setGateMessage("gateOtpResult", "⏳ Verifying code…", "");
    try {
        const formData = new URLSearchParams();
        formData.set("action",   "verify");
        formData.set("otp_code", code);

        const res  = await fetch(OTP_API, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: formData.toString() });
        const data = await res.json();

        if (!data.success) {
            setGateMessage("gateOtpResult", "✕ " + data.message, "error");
            otpInput.value = "";
            otpInput.focus();
            return;
        }

        // ✅ Fully authenticated
        unlockSite(data.account);

    } catch (err) {
        setGateMessage("gateOtpResult", "⚠ Server unreachable — is Apache/MySQL running?", "error");
    }
}

// Allow pressing Enter in the OTP box
document.addEventListener("DOMContentLoaded", function () {
    const otpInput = document.getElementById("gateOtpInput");
    if (otpInput) {
        otpInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") handleOtpVerify();
        });
    }
    const passwordInput = document.getElementById("gateLoginPassword");
    if (passwordInput) {
        passwordInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") handleGateLogin();
        });
    }
});


// ============================================================
//  RESEND OTP  —  60-second cooldown
// ============================================================

let resendTimerInterval = null;

function startResendCooldown() {
    const btn        = document.getElementById("otpResendBtn");
    const timerLabel = document.getElementById("otpResendTimer");
    if (!btn) return;
    btn.style.display        = "none";
    timerLabel.style.display = "inline";
    let seconds = 60;
    timerLabel.textContent   = `Resend available in ${seconds}s`;
    clearInterval(resendTimerInterval);
    resendTimerInterval = setInterval(function () {
        seconds--;
        if (seconds <= 0) {
            clearInterval(resendTimerInterval);
            timerLabel.style.display = "none";
            btn.style.display        = "inline";
        } else {
            timerLabel.textContent = `Resend available in ${seconds}s`;
        }
    }, 1000);
}

async function handleOtpResend() {
    setGateMessage("gateOtpResult", "⏳ Sending new code…", "");
    try {
        const formData = new URLSearchParams();
        formData.set("action", "resend");
        const res  = await fetch(OTP_API, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: formData.toString() });
        const data = await res.json();
        if (!data.success) { setGateMessage("gateOtpResult", "✕ " + data.message, "error"); return; }
        document.getElementById("otpMaskedEmail").textContent = data.masked_email || "your email";
        setGateMessage("gateOtpResult", "✔ New code sent! Check your inbox.", "success");
        document.getElementById("gateOtpInput").value = "";
        startResendCooldown();
    } catch (err) {
        setGateMessage("gateOtpResult", "⚠ Server unreachable.", "error");
    }
}


// ============================================================
//  REGISTER (gate)
// ============================================================

async function handleGateRegister() {
    const firstNameInput  = document.getElementById("gateRegisterFirstName");
    const middleNameInput = document.getElementById("gateRegisterMiddleName");
    const lastNameInput   = document.getElementById("gateRegisterLastName");
    const usernameInput   = document.getElementById("gateRegisterUsername");
    const emailInput      = document.getElementById("gateRegisterEmail");
    const passwordInput   = document.getElementById("gateRegisterPassword");
    const submitBtn       = document.querySelector("#gateRegisterPanel .login-gate-submit");

    const firstName  = firstNameInput.value.trim();
    const middleName = middleNameInput.value.trim();
    const lastName   = lastNameInput.value.trim();
    const username   = usernameInput.value.trim();
    const email      = emailInput.value.trim();
    const password   = passwordInput.value;

    if (!firstName || !lastName) { setGateMessage("gateRegisterResult", "⚠ Please enter your first name and surname.", "error"); return; }
    if (!username || !password)  { setGateMessage("gateRegisterResult", "⚠ Please choose a username and password.", "error"); return; }
    if (!email)                  { setGateMessage("gateRegisterResult", "⚠ Please enter your email address.", "error"); return; }

    // ── Loading state ──────────────────────────────────────────
    const originalLabel = submitBtn.textContent;
    const loadingSteps  = ["⏳ Creating account", "📧 Sending OTP email"];
    let   stepIndex     = 0;
    let   dotCount      = 0;
    submitBtn.disabled    = true;
    submitBtn.textContent = loadingSteps[0];

    const stepInterval = setInterval(() => {
        dotCount = (dotCount + 1) % 4;
        submitBtn.textContent = loadingSteps[stepIndex] + ".".repeat(dotCount);
    }, 400);

    // Advance label to email step after ~1.2 s
    const stepTimer = setTimeout(() => { stepIndex = 1; dotCount = 0; }, 1200);
    // ──────────────────────────────────────────────────────────

    function resetBtn() {
        clearInterval(stepInterval);
        clearTimeout(stepTimer);
        submitBtn.disabled    = false;
        submitBtn.textContent = originalLabel;
    }

    try {
        const formData = new URLSearchParams();
        formData.set("action",      "create");
        formData.set("first_name",  firstName);
        formData.set("middle_name", middleName);
        formData.set("last_name",   lastName);
        formData.set("username",    username);
        formData.set("email",       email);
        formData.set("password",    password);

        const res  = await fetch(ACCOUNTS_API, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: formData.toString() });
        const data = await res.json();

        if (!data.success) {
            resetBtn();
            setGateMessage("gateRegisterResult", "✕ " + data.message, "error");
            return;
        }

        // Clear fields
        [firstNameInput, middleNameInput, lastNameInput, usernameInput, emailInput, passwordInput]
            .forEach(el => el.value = "");

        resetBtn();

        // Account created — require OTP verification before entering the site
        await refreshAccounts();
        showOtpPanel(data.masked_email || "your email");
    } catch (err) {
        resetBtn();
        setGateMessage("gateRegisterResult", "⚠ Server unreachable — is Apache/MySQL running?", "error");
    }
}


// ============================================================
//  UNLOCK / LOGOUT
// ============================================================

function unlockSite(account) {
    currentLoggedInUser = { id: account.id, username: account.username, role: account.role || "viewer" };
    document.getElementById("loginGate").style.display   = "none";
    document.getElementById("siteContent").style.display = "block";
    document.getElementById("topbarUserLabel").textContent = `Logged in as ${account.username} (${currentLoggedInUser.role})`;
    applyRoleVisibility();
    refreshAccounts();
    refreshSkills();
    refreshIotData();
}

// Show/hide admin-only UI. Currently gates the "Manage User Roles"
// card inside the IoT Monitor section — the rest of the site
// (Manage Accounts, Manage Skills) stays open to every logged-in
// user, same as before the merge.
function applyRoleVisibility() {
    const isAdmin = currentLoggedInUser && currentLoggedInUser.role === "admin";
    const usersCard = document.getElementById("iotUsersCard");
    if (usersCard) usersCard.style.display = isAdmin ? "block" : "none";
}

async function handleLogout() {
    // Destroy the server-side session (clears pending_user_id + otp_verified)
    try { await fetch("API/logout.php"); } catch (_) { /* ignore network errors on logout */ }

    currentLoggedInUser = null;
    clearInterval(resendTimerInterval);
    clearInterval(iotPollInterval);
    document.getElementById("siteContent").style.display = "none";
    document.getElementById("loginGate").style.display   = "flex";
    document.getElementById("topbarUserLabel").textContent = "";
    showGateTab(accountsCache.length === 0 ? "register" : "login");
}


// ============================================================
//  BOOT
// ============================================================

window.onload = async function () {
    updateDateTime();
    setInterval(updateDateTime, 1000);
    await refreshAccounts();
    showGateTab(accountsCache.length === 0 ? "register" : "login");
};

// ============================================================
//  IOT MONITOR
//  Ported from the standalone IoT Monitor's dashboard.php.
//  That version was server-rendered PHP; this version fetches
//  JSON from API/iot_data.php and renders client-side, the same
//  pattern the Skills section already uses.
// ============================================================

let iotChart        = null;
let iotDailyChart    = null;
let iotPollInterval  = null;
let lastIotAlertState = false; // tracks normal->alert transitions, for the one-time popup

async function refreshIotData() {
    const from = document.getElementById("iotFromInput")?.value || "";
    const to   = document.getElementById("iotToInput")?.value   || "";
    const qs   = new URLSearchParams();
    if (from) qs.set("from", from);
    if (to)   qs.set("to", to);

    try {
        const res  = await fetch(`${IOT_API}${qs.toString() ? "?" + qs.toString() : ""}`);
        const data = await res.json();
        if (!data.success) { console.error("Failed to load IoT data:", data.message); return; }

        renderIotStatus(data.status);
        renderIotLatest(data.latest);
        renderIotStats(data.stats, !!(from || to));
        renderIotChart(data.chart);
        renderIotDailyChart(data.daily);
        renderIotHistory(data.rows);
        renderIotAlert(data.latest, data.threshold_temp, data.current_alert);

        if (currentLoggedInUser && currentLoggedInUser.role === "admin") {
            renderIotUsers();
        }
    } catch (err) {
        console.error("Network error loading IoT data:", err);
    }

    // Keep the dashboard live while the IoT section (or any part of
    // the page) is open — readings arrive every ~5s from the sensor.
    if (iotPollInterval) clearInterval(iotPollInterval);
    iotPollInterval = setInterval(refreshIotData, 5000);
}

function applyIotFilter() { refreshIotData(); }

function clearIotFilter() {
    document.getElementById("iotFromInput").value = "";
    document.getElementById("iotToInput").value   = "";
    refreshIotData();
}

function renderIotStatus(status) {
    const badge = document.getElementById("iotStatusBadge");
    if (!badge) return;
    const online = status === "Online";
    badge.textContent = online ? "● Online" : "● Offline";
    badge.style.color       = online ? "var(--accent-green)" : "#ff6868";
    badge.style.borderColor = online ? "var(--accent-green)" : "#ff686855";
    badge.style.background  = online ? "rgba(0,255,136,0.08)" : "rgba(255,60,60,0.08)";
}

function renderIotLatest(latest) {
    const tempEl = document.getElementById("iotLatestTemp");
    const humEl  = document.getElementById("iotLatestTime") ? document.getElementById("iotLatestHum") : null;
    const timeEl = document.getElementById("iotLatestTime");
    if (!tempEl) return;
    if (!latest) {
        tempEl.textContent = "No readings yet";
        if (humEl) humEl.textContent = "—";
        if (timeEl) timeEl.textContent = "—";
        return;
    }
    tempEl.textContent = `${escapeHtml(latest.temperature)} °C`;
    if (humEl) humEl.textContent = `${escapeHtml(latest.humidity)} %`;
    if (timeEl) timeEl.textContent = new Date(latest.created_at.replace(" ", "T")).toLocaleString();
}

// ------------------------------------------------------------
//  High-temperature alert — mirrors the Arduino's red/yellow
//  LED + buzzer state on the dashboard, shows a sticky warning
//  banner while the alert is active, and pops up a one-time
//  modal the moment the system crosses into alert (not on every
//  5s poll, so it doesn't spam the user while still in alert).
// ------------------------------------------------------------
function renderIotAlert(latest, thresholdTemp, currentAlert) {
    const thresholdLabel = document.getElementById("iotThresholdLabel");
    if (thresholdLabel && thresholdTemp != null) {
        thresholdLabel.textContent = `${thresholdTemp} °C`;
    }

    const ledBadge = document.getElementById("iotLedIndicator");
    if (ledBadge) {
        if (currentAlert) {
            ledBadge.textContent = "🔴 ALERT — Red LED + Buzzer";
            ledBadge.style.color       = "#ff5050";
            ledBadge.style.borderColor = "#ff505055";
            ledBadge.style.background  = "rgba(255,60,60,0.1)";
        } else {
            ledBadge.textContent = "🟡 Normal — Yellow LED";
            ledBadge.style.color       = "var(--accent-amber)";
            ledBadge.style.borderColor = "#ffb30055";
            ledBadge.style.background  = "rgba(255,179,0,0.08)";
        }
    }

    const banner = document.getElementById("iotAlertBanner");
    const detail = document.getElementById("iotAlertBannerDetail");
    if (banner) {
        banner.style.display = currentAlert ? "flex" : "none";
        if (currentAlert && detail && latest) {
            const when = latest.created_at ? new Date(latest.created_at.replace(" ", "T")).toLocaleTimeString() : "—";
            detail.textContent = `${latest.temperature} °C at ${when} — limit is ${thresholdTemp} °C`;
        }
    }

    // Pop up the modal only on the normal -> alert transition.
    if (currentAlert && !lastIotAlertState) {
        showTemperatureAlertModal(latest, thresholdTemp);
    }
    lastIotAlertState = !!currentAlert;
}

function showTemperatureAlertModal(latest, thresholdTemp) {
    const modal = document.getElementById("temperatureAlertModal");
    const msg   = document.getElementById("temperatureAlertModalMsg");
    if (!modal) return;
    if (msg) {
        const temp = latest ? escapeHtml(latest.temperature) : "—";
        msg.innerHTML = `The sensor just read <strong>${temp} °C</strong>, at or above the configured limit of <strong>${escapeHtml(thresholdTemp)} °C</strong>.<br>The red LED and buzzer are now ON.`;
    }
    modal.style.display = "flex";
}

function closeTemperatureAlertModal() {
    const modal = document.getElementById("temperatureAlertModal");
    if (modal) modal.style.display = "none";
}

function renderIotStats(stats, isFiltered) {
    const title = document.getElementById("iotStatsTitle");
    if (title) title.textContent = isFiltered ? "Selected Range Summary" : "All-Time Summary";

    const countBadge = document.getElementById("iotReadingCount");
    if (countBadge) countBadge.textContent = `(${stats.reading_count} readings)`;

    const tempAvg   = document.getElementById("iotTempAvg");
    const tempRange = document.getElementById("iotTempRange");
    const humAvg    = document.getElementById("iotHumAvg");
    const humRange  = document.getElementById("iotHumRange");

    if (stats.reading_count > 0) {
        if (tempAvg)   tempAvg.textContent   = `${stats.temp_avg} °C`;
        if (tempRange) tempRange.textContent = `${stats.temp_min} °C – ${stats.temp_max} °C`;
        if (humAvg)    humAvg.textContent    = `${stats.hum_avg} %`;
        if (humRange)  humRange.textContent  = `${stats.hum_min} % – ${stats.hum_max} %`;
    } else {
        [tempAvg, tempRange, humAvg, humRange].forEach(el => { if (el) el.textContent = "No data"; });
    }
}

function renderIotChart(chart) {
    const canvas = document.getElementById("iotChart");
    if (!canvas || typeof Chart === "undefined") return;

    const alerts = chart.alert || [];
    const tempPointColors = chart.temp.map((_, i) => (alerts[i] ? "#ff5050" : "#00e0ff"));
    const tempPointRadii  = chart.temp.map((_, i) => (alerts[i] ? 5 : 3));

    if (iotChart) { iotChart.destroy(); }
    iotChart = new Chart(canvas, {
        type: "line",
        data: {
            labels: chart.labels,
            datasets: [
                {
                    label: "Temperature (°C)",
                    data: chart.temp,
                    borderColor: "#00e0ff",
                    backgroundColor: "rgba(0,224,255,0.08)",
                    pointBackgroundColor: tempPointColors,
                    pointRadius: tempPointRadii,
                    borderWidth: 2, tension: 0.3
                },
                {
                    label: "Humidity (%)",
                    data: chart.hum,
                    borderColor: "#00f29c",
                    backgroundColor: "rgba(0,242,156,0.07)",
                    pointBackgroundColor: "#00f29c",
                    pointRadius: 3, borderWidth: 2, tension: 0.3
                }
            ]
        },
        options: iotChartOptions()
    });
}

function renderIotDailyChart(daily) {
    const card   = document.getElementById("iotDailyCard");
    const canvas = document.getElementById("iotDailyChart");
    if (!canvas || typeof Chart === "undefined") return;

    if (!daily.labels || daily.labels.length <= 1) {
        if (card) card.style.display = "none";
        return;
    }
    if (card) card.style.display = "block";

    if (iotDailyChart) { iotDailyChart.destroy(); }
    iotDailyChart = new Chart(canvas, {
        type: "line",
        data: {
            labels: daily.labels,
            datasets: [
                {
                    label: "Avg Temperature (°C)",
                    data: daily.avg_temp,
                    borderColor: "#00e0ff",
                    backgroundColor: "rgba(0,224,255,0.08)",
                    pointBackgroundColor: "#00e0ff",
                    pointRadius: 3, borderWidth: 2, tension: 0.3
                },
                {
                    label: "Avg Humidity (%)",
                    data: daily.avg_hum,
                    borderColor: "#00f29c",
                    backgroundColor: "rgba(0,242,156,0.07)",
                    pointBackgroundColor: "#00f29c",
                    pointRadius: 3, borderWidth: 2, tension: 0.3
                }
            ]
        },
        options: iotChartOptions()
    });
}

function iotChartOptions() {
    return {
        responsive: true,
        plugins: {
            legend: { labels: { color: "#d7ecf7", font: { family: "Exo 2" } } }
        },
        scales: {
            x: { ticks: { color: "#74a0b8" }, grid: { color: "rgba(255,255,255,0.04)" } },
            y: { beginAtZero: false, ticks: { color: "#74a0b8" }, grid: { color: "rgba(255,255,255,0.04)" } }
        }
    };
}

// ── IoT History Pagination ────────────────────────────────────
const IOT_PAGE_SIZE   = 10;
let iotHistoryCache   = [];   // full rows array from last API response
let iotCurrentPage    = 1;
let iotTotalPages     = 1;

function renderIotHistory(rows) {
    iotHistoryCache = rows || [];
    iotCurrentPage  = 1;
    iotTotalPages   = Math.max(1, Math.ceil(iotHistoryCache.length / IOT_PAGE_SIZE));
    _renderIotPage();
}

function iotGoToPage(page) {
    page = Math.max(1, Math.min(page, iotTotalPages));
    if (page === iotCurrentPage) return;
    iotCurrentPage = page;
    _renderIotPage();
}

function _renderIotPage() {
    const tbody      = document.getElementById("iotHistoryTableBody");
    const noMsg      = document.getElementById("noIotRowsMsg");
    const table      = document.getElementById("iotHistoryTable");
    const countBadge = document.getElementById("iotHistoryCount");
    const pagination = document.getElementById("iotPagination");
    const pageInfo   = document.getElementById("iotPageInfo");
    const btnFirst   = document.getElementById("iotPageFirst");
    const btnPrev    = document.getElementById("iotPagePrev");
    const btnNext    = document.getElementById("iotPageNext");
    const btnLast    = document.getElementById("iotPageLast");
    if (!tbody) return;

    tbody.innerHTML = "";
    if (countBadge) countBadge.textContent = `(${iotHistoryCache.length})`;

    if (iotHistoryCache.length === 0) {
        if (noMsg)      noMsg.style.display      = "block";
        if (table)      table.style.display      = "none";
        if (pagination) pagination.style.display = "none";
        return;
    }

    if (noMsg)  noMsg.style.display  = "none";
    if (table)  table.style.display  = "table";

    // Slice the page
    const start   = (iotCurrentPage - 1) * IOT_PAGE_SIZE;
    const pageRows = iotHistoryCache.slice(start, start + IOT_PAGE_SIZE);

    pageRows.forEach(function (r) {
        const row = document.createElement("tr");
        const recorded = r.created_at ? new Date(r.created_at.replace(" ", "T")).toLocaleString() : "—";
        const isAlert  = Number(r.alert) === 1;
        const alertCell = isAlert
            ? '<span style="color:#ff5050;">🔴 Alert</span>'
            : '<span style="color:var(--text-muted);">🟡 Normal</span>';
        row.innerHTML = `
            <td style="color:var(--text-muted);font-family:var(--font-mono);font-size:12px;">${escapeHtml(r.id)}</td>
            <td>${escapeHtml(r.temperature)} °C</td>
            <td>${escapeHtml(r.humidity)} %</td>
            <td>${alertCell}</td>
            <td style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);">${recorded}</td>`;
        tbody.appendChild(row);
    });

    // Pagination controls
    if (iotTotalPages <= 1) {
        if (pagination) pagination.style.display = "none";
        return;
    }
    if (pagination) pagination.style.display = "flex";

    const end = Math.min(start + IOT_PAGE_SIZE, iotHistoryCache.length);
    if (pageInfo) pageInfo.textContent = `${start + 1}–${end} of ${iotHistoryCache.length}`;

    const atFirst = iotCurrentPage === 1;
    const atLast  = iotCurrentPage === iotTotalPages;
    if (btnFirst) btnFirst.disabled = atFirst;
    if (btnPrev)  btnPrev.disabled  = atFirst;
    if (btnNext)  btnNext.disabled  = atLast;
    if (btnLast)  btnLast.disabled  = atLast;
}

// ------------------------------------------------------------
//  Admin-only: manage user roles, reusing accountsCache (which
//  now includes `role` from the merged accounts table) instead
//  of a separate users table/endpoint.
// ------------------------------------------------------------

function renderIotUsers() {
    const tbody = document.getElementById("iotUsersTableBody");
    if (!tbody) return;
    tbody.innerHTML = "";
    accountsCache.forEach(function (account, index) {
        const isMe      = currentLoggedInUser && currentLoggedInUser.id == account.id;
        const fullName  = [account.first_name, account.middle_name, account.last_name].filter(Boolean).join(" ");
        const isAdmin   = account.role === "admin";
        const row = document.createElement("tr");
        row.innerHTML = `
            <td style="color:var(--text-muted);font-family:var(--font-mono);font-size:12px;">${index + 1}</td>
            <td>${escapeHtml(fullName)}</td>
            <td>${escapeHtml(account.username)}${isMe ? ' <span class="badge">You</span>' : ''}</td>
            <td><span class="badge" style="${isAdmin ? '' : 'color:var(--text-muted);border-color:var(--border-dim);background:transparent;'}">${escapeHtml(account.role)}</span></td>
            <td class="crud-actions-cell">
                ${isAdmin
                    ? `<button class="crud-btn crud-btn-cancel" onclick="setAccountRole(${account.id}, 'viewer')" ${isMe ? "disabled title=\"You can't remove your own admin role.\"" : ""}>↓ Demote to Viewer</button>`
                    : `<button class="crud-btn crud-btn-edit" onclick="setAccountRole(${account.id}, 'admin')">↑ Promote to Admin</button>`}
            </td>`;
        tbody.appendChild(row);
    });
}

async function setAccountRole(id, role) {
    try {
        const formData = new URLSearchParams();
        formData.set("action", "change_role");
        formData.set("id", id);
        formData.set("role", role);

        const res  = await fetch(ACCOUNTS_API, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: formData.toString() });
        const data = await res.json();

        if (!data.success) { alert(data.message || "Failed to update role."); return; }

        await refreshAccounts();
        renderIotUsers();
    } catch (err) {
        alert("Network error updating role.");
    }
}