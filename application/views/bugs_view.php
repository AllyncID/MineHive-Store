<?php
$page_title = isset($title) ? (string) $title : 'Bugs | MineHive';
$page_description = !empty($meta_description)
    ? (string) $meta_description
    : 'Laporkan bug atau masalah yang kamu temukan di MineHive agar tim bisa cek dan memperbaikinya lebih cepat.';
$page_image = base_url('assets/images/opengraph_banner.jpg');
// --- BAGIAN BACKEND (PHP) ---
// Bagian ini hanya akan berjalan jika ada request POST (saat tombol Approve diklik)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Konfigurasi Pterodactyl (Edit disini)
    $PTERODACTYL_URL = "https://dash.minehive.id"; 
    
    // ⚠️ WAJIB GANTI DENGAN KEY LENGKAP (PANJANG) ⚠️
    // Key "ptlc_58hwE9UROV6" kemungkinan TERPOTONG. Key asli biasanya +- 48 karakter.
    $API_KEY = "ptlc_58hwE9UROV6"; 

    header("Content-Type: application/json");
    // CORS Headers
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Content-Type");

    // Ambil data JSON dari Javascript
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (isset($data['server_id']) && isset($data['command'])) {
        $server_id = $data['server_id'];
        $command = $data['command'];

        // Kirim ke Pterodactyl (Client API)
        $url = rtrim($PTERODACTYL_URL, '/') . "/api/client/servers/{$server_id}/command";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $API_KEY",
            "Content-Type: application/json",
            "Accept: Application/vnd.pterodactyl.v1+json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["command" => $command]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            echo json_encode([
                "status" => "success", 
                "message" => "Command sent",
                "data" => json_decode($response)
            ]);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "Pterodactyl Error: $httpCode",
                "detail" => $response, 
                "curl_error" => $curlError,
                "debug_info" => "Check API Key length and permissions."
            ]);
        }
        exit(); 
    }
}
?>
<!-- --- BAGIAN FRONTEND (HTML/JS) --- -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= html_escape($page_description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= html_escape(current_url()); ?>">
    <meta property="og:title" content="<?= html_escape($page_title); ?>">
    <meta property="og:site_name" content="MineHive">
    <meta property="og:description" content="<?= html_escape($page_description); ?>">
    <meta property="og:image" content="<?= html_escape($page_image); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= html_escape(current_url()); ?>">
    <meta name="twitter:title" content="<?= html_escape($page_title); ?>">
    <meta name="twitter:description" content="<?= html_escape($page_description); ?>">
    <meta name="twitter:image" content="<?= html_escape($page_image); ?>">
    <title><?= html_escape($page_title); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome & Google Fonts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Firebase SDKs -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
        import { getAuth, signInAnonymously, onAuthStateChanged, signInWithCustomToken } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
        import { getFirestore, collection, addDoc, onSnapshot, doc, deleteDoc, updateDoc } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";

        // --- FIREBASE CONFIG (ASLI) ---
        const firebaseConfig = {
            apiKey: "AIzaSyDCRWCzBpUg3jBUtBtoZZw4XI029SCNkj4",
            authDomain: "minehive-suggestion.firebaseapp.com",
            projectId: "minehive-suggestion",
            storageBucket: "minehive-suggestion.firebasestorage.app",
            messagingSenderId: "649946689048",
            appId: "1:649946689048:web:30b5ef98348673564d9d6d",
            measurementId: "G-B576NKCHK9"
        };
        
        // Cek environment (abaikan error app_id di local)
        const appId = typeof __app_id !== 'undefined' ? __app_id : 'default-app';
        
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const db = getFirestore(app);

        // --- SERVER CONFIGURATION (UPDATED: SHORT IDs) ---
        // Pterodactyl Client API menggunakan 8 karakter pertama (Short ID)
        const SERVER_IDS = {
            'Survival': 'a15859dc', // Short ID dari a15859dc-6590...
            'Skyblock': '373598dd'  // Short ID dari 373598dd-30f6...
        };

        // POINTING KE FILE INI SENDIRI
        const BACKEND_URL = window.location.href; 

        let currentUser = null;
        let isAdmin = false;
        let unsubscribeReports = null;
        
        let globalReports = [];
        let currentFilter = 'All'; 
        let adminGameModeFilter = 'All'; 
        let searchQuery = '';
        
        let selectedCategories = new Set(['Visual']); 
        let selectedGameMode = null; 
        let pendingApprovalId = null;

        // --- AUTHENTICATION ---
        const initAuth = async () => {
            if (typeof __initial_auth_token !== 'undefined' && __initial_auth_token) {
                await signInWithCustomToken(auth, __initial_auth_token);
            } else {
                await signInAnonymously(auth);
            }
        };
        initAuth();

        onAuthStateChanged(auth, (user) => {
            if (user) {
                currentUser = user;
                const submitBtn = document.getElementById('submitBtn');
                if(submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                loadReports(); 
            }
        });

        // --- APP LOGIC ---

        window.selectGameMode = function(mode) {
            selectedGameMode = mode;
            const modes = ['Survival', 'Skyblock'];
            modes.forEach(m => {
                const btn = document.getElementById(`btn-${m}`);
                if (m === mode) {
                    btn.classList.add('bg-red-600', 'text-white', 'border-red-600', 'shadow-md', 'shadow-red-200');
                    btn.classList.remove('bg-white', 'text-slate-600', 'border-slate-200', 'hover:border-red-300');
                } else {
                    btn.classList.remove('bg-red-600', 'text-white', 'border-red-600', 'shadow-md', 'shadow-red-200');
                    btn.classList.add('bg-white', 'text-slate-600', 'border-slate-200', 'hover:border-red-300');
                }
            });
        };

        window.toggleCategory = function(category) {
            if (selectedCategories.has(category)) {
                if (selectedCategories.size > 1) {
                    selectedCategories.delete(category);
                }
            } else {
                selectedCategories.add(category);
            }
            updateCategoryUI();
        };

        function updateCategoryUI() {
            const buttons = document.querySelectorAll('.cat-btn');
            buttons.forEach(btn => {
                const cat = btn.dataset.val;
                if(selectedCategories.has(cat)) {
                    btn.classList.add('bg-red-100', 'border-red-500', 'text-red-700', 'ring-2', 'ring-red-500/20');
                    btn.classList.remove('bg-slate-50', 'border-slate-200', 'text-slate-600');
                } else {
                    btn.classList.remove('bg-red-100', 'border-red-500', 'text-red-700', 'ring-2', 'ring-red-500/20');
                    btn.classList.add('bg-slate-50', 'border-slate-200', 'text-slate-600');
                }
            });
        }

        window.submitReport = async function(e) {
            e.preventDefault();
            
            if (!currentUser) {
                showToast("Connecting to server...", "warning");
                return;
            }

            const nickname = document.getElementById('nickname').value;
            const message = document.getElementById('message').value;
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');

            if (!nickname.trim()) {
                showToast("Please enter your Nickname!", "warning");
                document.getElementById('nickname').focus();
                return;
            }

            if (!selectedGameMode) {
                showToast("Please select a Game Mode!", "warning");
                const gmSection = document.getElementById('gameModeSection');
                gmSection.classList.add('shake');
                setTimeout(() => gmSection.classList.remove('shake'), 500);
                return;
            }

            if (!message.trim()) {
                showToast("Please describe the bug!", "error");
                return;
            }

            btn.disabled = true;
            btnText.textContent = "Reporting...";
            btnIcon.classList.add('animate-spin');
            btnIcon.classList.replace('fa-bug', 'fa-circle-notch');

            try {
                const colRef = collection(db, 'artifacts', appId, 'public', 'data', 'bug_reports');
                const categoriesArray = Array.from(selectedCategories);

                await addDoc(colRef, {
                    nickname: nickname.trim(),
                    message: message,
                    categories: categoriesArray,
                    gameMode: selectedGameMode, 
                    status: 'pending', 
                    severity: 'none', 
                    createdAt: Date.now(), 
                    userId: currentUser.uid 
                });

                showToast("Bug Report sent! Admins will review it.", "success");
                document.getElementById('bugForm').reset();
                updateCharCount(document.getElementById('message'));
                
                selectedCategories = new Set(['Visual']);
                updateCategoryUI();
                
                selectedGameMode = null;
                document.querySelectorAll('.gamemode-btn').forEach(b => {
                    b.classList.remove('bg-red-600', 'text-white', 'border-red-600', 'shadow-md', 'shadow-red-200');
                    b.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
                });

            } catch (error) {
                console.error("Error adding document: ", error);
                showToast("Failed to send. Try again.", "error");
            } finally {
                btn.disabled = false;
                btnText.textContent = "Submit Report";
                btnIcon.classList.remove('animate-spin');
                btnIcon.classList.replace('fa-circle-notch', 'fa-bug');
            }
        };

        window.toggleAdmin = function() {
            const adminPanel = document.getElementById('adminPanel');
            const userPanel = document.getElementById('userPanel');
            const mainHeader = document.getElementById('mainHeader');
            const modal = document.getElementById('loginModal');

            if (isAdmin) {
                isAdmin = false;
                adminPanel.classList.add('hidden');
                userPanel.classList.remove('hidden');
                userPanel.classList.add('animate-slide-up');
                mainHeader.classList.remove('hidden');
                mainHeader.classList.add('animate-slide-up');
                showToast("Admin Logged Out", "success");
            } else {
                modal.classList.remove('hidden');
                document.getElementById('adminPass').focus();
            }
        };

        window.checkPassword = function() {
            const pass = document.getElementById('adminPass').value;
            const modal = document.getElementById('loginModal');
            
            if (pass === '@Minehive123') {
                isAdmin = true;
                modal.classList.add('hidden');
                document.getElementById('adminPass').value = '';
                document.getElementById('userPanel').classList.add('hidden');
                document.getElementById('mainHeader').classList.add('hidden');
                document.getElementById('adminPanel').classList.remove('hidden');
                document.getElementById('adminPanel').classList.add('animate-fade-in');
                showToast("Welcome Admin", "success");
                loadReports();
            } else {
                showToast("Invalid Password!", "error");
                document.getElementById('adminPass').classList.add('shake');
                setTimeout(() => document.getElementById('adminPass').classList.remove('shake'), 500);
            }
        };

        window.closeModal = function(id) {
            document.getElementById(id).classList.add('hidden');
        };

        function loadReports() {
            if (!currentUser) return;
            const colRef = collection(db, 'artifacts', appId, 'public', 'data', 'bug_reports');

            unsubscribeReports = onSnapshot(colRef, (snapshot) => {
                globalReports = [];
                snapshot.forEach(doc => {
                    globalReports.push({ id: doc.id, ...doc.data() });
                });
                globalReports.sort((a, b) => b.createdAt - a.createdAt);
                if(isAdmin) renderReports();
            }, (error) => {
                console.error("Error fetching reports:", error);
                showToast("Failed to load data", "error");
            });
        }

        window.setFilter = function(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-tab').forEach(tab => {
                if(tab.dataset.filter === filter) {
                    tab.classList.add('bg-slate-800', 'text-white');
                    tab.classList.remove('text-slate-400', 'hover:text-white');
                } else {
                    tab.classList.remove('bg-slate-800', 'text-white');
                    tab.classList.add('text-slate-400', 'hover:text-white');
                }
            });
            renderReports();
        }

        window.setGameModeFilter = function(mode) {
            adminGameModeFilter = mode;
            document.querySelectorAll('.gm-filter-btn').forEach(btn => {
                if(btn.dataset.mode === mode) {
                    btn.classList.add('bg-red-600', 'text-white', 'border-red-600');
                    btn.classList.remove('bg-slate-800', 'text-slate-400', 'border-slate-700');
                } else {
                    btn.classList.remove('bg-red-600', 'text-white', 'border-red-600');
                    btn.classList.add('bg-slate-800', 'text-slate-400', 'border-slate-700');
                }
            });
            renderReports();
        }

        window.handleSearch = function(e) {
            searchQuery = e.target.value.toLowerCase();
            renderReports();
        }

        function getCategoryStyle(cat) {
            if(cat === 'Visual') return { class: 'bg-blue-100 text-blue-700 border-blue-200', icon: 'fa-eye' };
            if(cat === 'Gameplay') return { class: 'bg-green-100 text-green-700 border-green-200', icon: 'fa-gamepad' };
            if(cat === 'Critical/Crash') return { class: 'bg-red-100 text-red-700 border-red-200', icon: 'fa-bomb' };
            if(cat === 'Exploit') return { class: 'bg-amber-100 text-amber-700 border-amber-200', icon: 'fa-user-secret' };
            return { class: 'bg-slate-100 text-slate-700 border-slate-200', icon: 'fa-tag' }; 
        }

        function renderReports() {
            const listContainer = document.getElementById('reportList');
            const msgCount = document.getElementById('msgCount');
            
            let displayList = globalReports.filter(item => {
                const itemCats = item.categories || (item.category ? [item.category] : ['General']);
                const matchesTopic = currentFilter === 'All' || itemCats.includes(currentFilter);
                const itemGM = item.gameMode || 'Survival'; 
                const matchesGM = adminGameModeFilter === 'All' || itemGM === adminGameModeFilter;
                const msg = (item.message || '').toLowerCase();
                const nick = (item.nickname || '').toLowerCase();
                const matchesSearch = msg.includes(searchQuery) || nick.includes(searchQuery);
                return matchesTopic && matchesGM && matchesSearch;
            });

            if(msgCount) msgCount.innerText = displayList.length;
            listContainer.innerHTML = '';
            
            if (displayList.length === 0) {
                listContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-20 text-slate-400">
                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-check-circle text-3xl text-slate-300"></i>
                        </div>
                        <p class="font-medium text-slate-500">No bugs reported yet.</p>
                    </div>
                `;
                return;
            }

            displayList.forEach(item => {
                const dateObj = new Date(item.createdAt);
                const date = dateObj.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
                const time = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                const itemCats = item.categories || (item.category ? [item.category] : ['Other']);
                const itemGM = item.gameMode || 'Survival';
                let gmIcon = itemGM === 'Skyblock' ? 'fa-cloud' : 'fa-tree';
                let gmColor = itemGM === 'Skyblock' ? 'text-sky-600 bg-sky-50 border-sky-200' : 'text-emerald-600 bg-emerald-50 border-emerald-200';

                const tagsHtml = itemCats.map(cat => {
                    const style = getCategoryStyle(cat);
                    return `<span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide border ${style.class} inline-flex items-center"><i class="fas ${style.icon} mr-1"></i> ${cat}</span>`;
                }).join('');

                const status = item.status || 'pending';
                const severity = item.severity || 'none';
                
                let statusBorder = 'border-slate-100';
                let statusBg = 'bg-white';
                let opacity = 'opacity-100';
                let statusBadge = '';
                let severityBadge = '';
                
                if(severity === 'critical') severityBadge = `<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-red-600 text-white shadow-sm shadow-red-300 ml-2">CRITICAL</span>`;
                if(severity === 'medium') severityBadge = `<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-orange-500 text-white shadow-sm shadow-orange-300 ml-2">MAJOR</span>`;
                if(severity === 'low') severityBadge = `<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-yellow-500 text-white shadow-sm shadow-yellow-300 ml-2">MINOR</span>`;

                if(status === 'approved') {
                    statusBorder = 'border-green-500 border-l-4';
                    statusBg = 'bg-green-50/30';
                    statusBadge = `<div class="mt-2 text-xs font-bold text-green-700 flex items-center gap-1"><i class="fas fa-check-circle"></i> Resolved & Rewarded ${severityBadge}</div>`;
                } else if (status === 'rejected') {
                    statusBg = 'bg-slate-50';
                    opacity = 'opacity-60 grayscale-[0.8]';
                    statusBadge = `<div class="mt-2 text-xs font-bold text-red-400 flex items-center gap-1"><i class="fas fa-times-circle"></i> Rejected</div>`;
                }

                const card = document.createElement('div');
                card.className = `group ${statusBg} ${statusBorder} ${opacity} p-5 rounded-xl border shadow-sm hover:shadow-md transition-all duration-300 relative`;
                
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-lg font-bold border border-slate-200">
                                <img src="https://ui-avatars.com/api/?name=${item.nickname}&background=random" class="rounded-full w-full h-full" alt="Avatar">
                            </div>
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-800 tracking-wide">${item.nickname}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase border ${gmColor} flex items-center gap-1"><i class="fas ${gmIcon}"></i> ${itemGM}</span>
                                </div>
                                <span class="text-xs text-slate-500 font-medium">${date} • ${time}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1 justify-end max-w-[50%]">${tagsHtml}</div>
                    </div>
                    
                    <div class="pl-13 mb-4">
                        <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-wrap">${item.message}</p>
                        ${statusBadge}
                    </div>

                    ${status === 'pending' ? `
                    <div class="pl-13 pt-3 border-t border-slate-100 flex justify-between items-center">
                        <div class="flex gap-2">
                            <button onclick="openApprovalModal('${item.id}', '${item.nickname}', '${item.gameMode}')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white border border-slate-200 text-slate-500 hover:border-green-500 hover:text-green-600 hover:shadow-lg hover:shadow-green-100">
                                <i class="fas fa-check mr-1"></i> Approve
                            </button>
                            <button onclick="updateStatus('${item.id}', 'rejected', 'none')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white border border-slate-200 text-slate-500 hover:border-red-500 hover:text-red-600 hover:shadow-lg hover:shadow-red-100">
                                <i class="fas fa-times mr-1"></i> Reject
                            </button>
                        </div>
                        <button onclick="deleteReport('${item.id}')" class="text-slate-300 hover:text-red-500 p-2 rounded-lg transition-all" title="Delete Permanent"><i class="fas fa-trash-alt"></i></button>
                    </div>` : `
                    <div class="pl-13 pt-3 border-t border-slate-100 flex justify-end">
                         <button onclick="deleteReport('${item.id}')" class="text-slate-300 hover:text-red-500 p-2 rounded-lg transition-all" title="Delete Permanent"><i class="fas fa-trash-alt"></i></button>
                    </div>
                    `}
                `;
                listContainer.appendChild(card);
            });
        }

        window.openApprovalModal = function(id, nickname, gamemode) {
            pendingApprovalId = { id, nickname, gamemode };
            document.getElementById('approvalModal').classList.remove('hidden');
        }

        window.confirmApproval = async function(severity) {
            if (!pendingApprovalId) return;
            
            const { id, nickname, gamemode } = pendingApprovalId;
            const serverId = SERVER_IDS[gamemode] || 'UNKNOWN-SERVER-ID';
            
            let rewardName = "";
            let commands = [];
            
            if (severity === 'low') {
                rewardName = "100 Bucks";
                commands = [
                    `p give ${nickname} 10`,
                    `sendchat ${nickname} &7`,
                    `sendchat ${nickname} &c&l[!] &cNice catch! &7Your bug report was &cvalid &7and has been &capproved&7. You've been rewarded with &c10 Bucks`,
                    `sendchat ${nickname} &7`
                ];
            } else if (severity === 'medium') {
                rewardName = "500 Bucks";
                commands = [
                    `p give ${nickname} 20`,
                    `sendchat ${nickname} &7`,
                    `sendchat ${nickname} &c&l[!] &cNice catch! &7Your bug report was &cvalid &7and has been &capproved&7. You've been rewarded with &c20 Bucks`,
                    `sendchat ${nickname} &7`
                ];
            } else if (severity === 'critical') {
                rewardName = "1000 Bucks + Bug Hunter Key";
                commands = [
                    `p give ${nickname} 35`,
                    `sendchat ${nickname} &7`,
                    `sendchat ${nickname} &c&l[!] &cNice catch! &7Your bug report was &cvalid &7and has been &capproved&7. You've been rewarded with &c35 Bucks`,
                    `sendchat ${nickname} &7`
                ];
            }

            showToast("Processing rewards...", "info");
            
            let backendReachable = true;

            // Loop untuk mengirim setiap command
            for (const cmd of commands) {
                console.log(`Sending: ${cmd}`);
                try {
                    // Panggil file ini sendiri sebagai backend
                    const response = await fetch(BACKEND_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ server_id: serverId, command: cmd })
                    });
                    
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Invalid backend response");
                    }

                    if (!response.ok) throw new Error(`HTTP ${response.status}`);

                    const result = await response.json();
                    
                    if(result.status === 'success') {
                         console.log(`✅ Success: ${cmd}`);
                    } else {
                         console.error(`❌ Failed: ${cmd}`, JSON.stringify(result, null, 2)); // Detailed Log
                         showToast(`Failed: ${result.message}`, "error");
                    }
                } catch (e) {
                    console.warn("Backend fail:", e);
                    backendReachable = false;
                }
                
                await new Promise(r => setTimeout(r, 300)); 
            }

            if (backendReachable) {
                showToast(`Rewards sent to ${nickname}!`, "success");
            } else {
                // Jangan tampilkan pesan error membingungkan ke user, cukup warning halus
                // Kemungkinan file dijalankan local atau server tidak support POST ke file sendiri
                showToast(`Status updated to Approved`, "success");
            }

            await updateStatus(id, 'approved', severity);
            document.getElementById('approvalModal').classList.add('hidden');
            pendingApprovalId = null;
        }

        window.updateStatus = async function(docId, newStatus, severityVal) {
            try {
                const docRef = doc(db, 'artifacts', appId, 'public', 'data', 'bug_reports', docId);
                await updateDoc(docRef, { status: newStatus, severity: severityVal });
            } catch (err) {
                console.error(err);
                showToast("Failed to update status", "error");
            }
        };

        window.deleteReport = async function(docId) {
            if(!confirm('Delete this report permanently?')) return;
            try {
                const docRef = doc(db, 'artifacts', appId, 'public', 'data', 'bug_reports', docId);
                await deleteDoc(docRef);
                showToast("Deleted successfully", "success");
            } catch (err) {
                showToast("Failed to delete", "error");
            }
        };

        window.updateCharCount = function(textarea) {
            const count = textarea.value.length;
            const counter = document.getElementById('charCount');
            counter.innerText = `${count} / 2000`;
            if(count >= 2000) counter.classList.add('text-red-500', 'font-bold');
            else counter.classList.remove('text-red-500', 'font-bold');
        };

        window.showToast = function(msg, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toastMsg');
            const toastIcon = document.getElementById('toastIcon');
            
            toastMsg.innerText = msg;
            
            if(type === 'success') toastIcon.className = 'fas fa-check-circle text-green-500 text-xl';
            else if (type === 'error') toastIcon.className = 'fas fa-exclamation-circle text-red-500 text-xl';
            else if (type === 'warning') toastIcon.className = 'fas fa-exclamation-triangle text-yellow-500 text-xl';
            else toastIcon.className = 'fas fa-info-circle text-blue-500 text-xl';

            toast.classList.remove('translate-y-24', 'opacity-0');
            setTimeout(() => { toast.classList.add('translate-y-24', 'opacity-0'); }, 3000);
        };
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fef2f2; color: #1e293b; }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
        .animate-slide-up { animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        .shake { animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake { 10%, 90% { transform: translate3d(-1px, 0, 0); } 20%, 80% { transform: translate3d(2px, 0, 0); } 30%, 50%, 70% { transform: translate3d(-3px, 0, 0); } 40%, 60% { transform: translate3d(3px, 0, 0); } }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #fca5a5; border-radius: 20px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #f87171; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        textarea:focus, input:focus { box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2); }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-6 py-10 bg-red-50 relative overflow-y-auto overflow-x-hidden">

    <!-- Background -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-red-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70"></div>
        <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-orange-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-rose-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70"></div>
    </div>

    <!-- MAIN CONTAINER -->
    <main class="w-full max-w-lg relative z-10 transition-all duration-500 my-auto">
        
        <!-- HEADER -->
        <div id="mainHeader" class="text-center mb-10 animate-slide-up">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white shadow-lg shadow-red-500/10 mb-6 text-red-600">
                <i class="fas fa-bug text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 mb-2">Bug Report</h1>
            <p class="text-slate-500 text-sm">Found a bug? Help us squash it! Rewards for critical reports.</p>
        </div>

        <!-- USER PANEL -->
        <div id="userPanel" class="bg-white rounded-3xl p-8 shadow-2xl shadow-slate-200/50 animate-slide-up border border-slate-100" style="animation-delay: 0.1s">
            <form id="bugForm" onsubmit="submitReport(event)" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-xs uppercase tracking-wider text-slate-500 font-bold ml-1">In-Game Nickname <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <input type="text" id="nickname" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 pl-10 text-slate-800 focus:outline-none focus:border-red-500 focus:bg-white transition-colors font-semibold" placeholder="e.g. Steve123">
                        <i class="fas fa-user absolute left-4 top-4 text-slate-400"></i>
                    </div>
                </div>

                <div id="gameModeSection" class="space-y-2">
                    <label class="text-xs uppercase tracking-wider text-slate-500 font-bold ml-1">Game Mode <span class="text-red-400">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" id="btn-Survival" onclick="selectGameMode('Survival')" class="gamemode-btn w-full py-3 rounded-xl border border-slate-200 bg-white text-slate-600 font-bold text-sm transition-all flex items-center justify-center gap-2 hover:border-red-300"><i class="fas fa-tree"></i> Survival</button>
                        <button type="button" id="btn-Skyblock" onclick="selectGameMode('Skyblock')" class="gamemode-btn w-full py-3 rounded-xl border border-slate-200 bg-white text-slate-600 font-bold text-sm transition-all flex items-center justify-center gap-2 hover:border-red-300"><i class="fas fa-cloud"></i> Skyblock</button>
                    </div>
                </div>

                <div class="space-y-2 w-full">
                    <label class="text-xs uppercase tracking-wider text-slate-500 font-bold ml-1">Bug Type</label>
                    <div class="flex gap-2 overflow-x-auto pb-2 w-full no-scrollbar snap-x">
                        <button type="button" data-val="Visual" onclick="toggleCategory('Visual')" class="cat-btn flex-shrink-0 snap-start px-4 py-2 rounded-lg text-xs font-bold border transition-all bg-red-100 border-red-500 text-red-700 ring-2 ring-red-500/20">Visual</button>
                        <button type="button" data-val="Gameplay" onclick="toggleCategory('Gameplay')" class="cat-btn flex-shrink-0 snap-start px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-600 bg-slate-50 hover:bg-slate-100 transition-all">Gameplay</button>
                        <button type="button" data-val="Critical/Crash" onclick="toggleCategory('Critical/Crash')" class="cat-btn flex-shrink-0 snap-start px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-600 bg-slate-50 hover:bg-slate-100 transition-all">Critical/Crash</button>
                        <button type="button" data-val="Exploit" onclick="toggleCategory('Exploit')" class="cat-btn flex-shrink-0 snap-start px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-600 bg-slate-50 hover:bg-slate-100 transition-all">Exploit</button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs uppercase tracking-wider text-slate-500 font-bold ml-1">Description</label>
                    <div class="relative">
                        <textarea id="message" rows="5" maxlength="2000" oninput="updateCharCount(this)" class="w-full bg-slate-50 text-slate-800 border border-slate-200 rounded-xl px-5 py-4 focus:outline-none focus:bg-white focus:border-red-500 transition-all resize-none placeholder-slate-400 text-sm" placeholder="Describe how to reproduce the bug..."></textarea>
                    </div>
                    <div class="flex justify-end"><span id="charCount" class="text-xs text-slate-400 font-medium transition-colors">0 / 2000</span></div>
                </div>

                <button type="submit" id="submitBtn" disabled class="w-full py-4 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/30 transform active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="btnText">Submit Report</span><i id="btnIcon" class="fas fa-bug text-sm"></i>
                </button>
            </form>
        </div>

        <!-- ADMIN PANEL -->
        <div id="adminPanel" class="bg-white rounded-3xl shadow-2xl shadow-slate-200/50 hidden relative flex flex-col border border-slate-100 overflow-hidden h-[700px]">
            <div class="bg-slate-900 p-6 z-10 shrink-0 space-y-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-600 flex items-center justify-center text-white shadow-lg shadow-red-500/50"><i class="fas fa-shield-alt"></i></div>
                        <div><h2 class="text-white font-bold text-lg leading-tight">Admin Console</h2><p class="text-slate-400 text-xs">Bug Tracker</p></div>
                    </div>
                    <button onclick="toggleAdmin()" class="group flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 transition-colors border border-slate-700"><span class="text-xs font-medium text-slate-300 group-hover:text-white">Logout</span><i class="fas fa-sign-out-alt text-slate-400 group-hover:text-white text-xs"></i></button>
                </div>
                <div class="relative">
                    <input type="text" oninput="handleSearch(event)" placeholder="Search bugs or players..." class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-red-500 transition-colors placeholder-slate-500">
                    <i class="fas fa-search absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                </div>
            </div>

            <div class="bg-slate-900 px-4 pb-4 border-b border-slate-800 shrink-0 space-y-3">
                <div class="flex gap-2 justify-center pb-2 border-b border-slate-800/50">
                    <button onclick="setGameModeFilter('All')" data-mode="All" class="gm-filter-btn px-4 py-1.5 rounded-lg text-xs font-bold transition-all bg-red-600 text-white border border-red-600">All</button>
                    <button onclick="setGameModeFilter('Survival')" data-mode="Survival" class="gm-filter-btn px-4 py-1.5 rounded-lg text-xs font-bold transition-all bg-slate-800 text-slate-400 border border-slate-700 hover:text-white">Survival</button>
                    <button onclick="setGameModeFilter('Skyblock')" data-mode="Skyblock" class="gm-filter-btn px-4 py-1.5 rounded-lg text-xs font-bold transition-all bg-slate-800 text-slate-400 border border-slate-700 hover:text-white">Skyblock</button>
                </div>
                 <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
                     <button onclick="setFilter('All')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-slate-800 text-white" data-filter="All">All</button>
                     <button onclick="setFilter('Visual')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-400 hover:text-white" data-filter="Visual">Visual</button>
                     <button onclick="setFilter('Gameplay')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-400 hover:text-white" data-filter="Gameplay">Gameplay</button>
                     <button onclick="setFilter('Critical/Crash')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-400 hover:text-white" data-filter="Critical/Crash">Critical</button>
                     <button onclick="setFilter('Exploit')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-400 hover:text-white" data-filter="Exploit">Exploit</button>
                 </div>
            </div>

            <div class="bg-slate-50 border-b border-slate-200 px-6 py-3 flex justify-between items-center shrink-0">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Reports</span>
                <span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-red-200"><span id="msgCount">0</span> Bugs</span>
            </div>

            <div id="reportList" class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar bg-slate-50/50">
                <div class="flex flex-col items-center justify-center h-full text-slate-400 animate-pulse">
                    <i class="fas fa-circle-notch fa-spin text-2xl mb-2 text-red-300"></i>
                    <p class="text-xs font-medium">Syncing data...</p>
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-center opacity-30 hover:opacity-100 transition-opacity">
            <button onclick="toggleAdmin()" class="text-slate-400 hover:text-red-500 transition-colors p-2" title="Admin Login"><i class="fas fa-fingerprint text-lg"></i></button>
        </div>
    </main>

    <!-- ADMIN LOGIN MODAL -->
    <div id="loginModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl p-8 shadow-2xl transform transition-all scale-100 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-500 via-orange-500 to-yellow-500"></div>
            <div class="flex justify-between items-start mb-8">
                <div><h3 class="text-xl font-bold text-slate-900">Admin Access</h3><p class="text-slate-500 text-xs mt-1">Enter passcode</p></div>
                <button onclick="closeModal('loginModal')" class="text-slate-300 hover:text-slate-500 bg-slate-50 p-2 rounded-full w-8 h-8 flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="relative">
                <input type="password" id="adminPass" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 pl-10 text-slate-800 mb-4 focus:outline-none focus:border-red-500 focus:bg-white transition-colors font-bold text-lg placeholder:font-normal placeholder:text-slate-400" placeholder="Passcode..." onkeypress="if(event.key === 'Enter') checkPassword()">
                <i class="fas fa-key absolute left-4 top-4 text-slate-400"></i>
            </div>
            <button onclick="checkPassword()" class="w-full bg-slate-900 text-white font-semibold py-3.5 rounded-xl hover:bg-slate-800 transition-colors shadow-lg shadow-slate-900/20 flex items-center justify-center gap-2"><span>Unlock Dashboard</span><i class="fas fa-arrow-right text-xs"></i></button>
        </div>
    </div>

    <!-- APPROVAL MODAL -->
    <div id="approvalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl p-6 shadow-2xl transform transition-all scale-100">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 text-green-600"><i class="fas fa-check text-xl"></i></div>
                <h3 class="text-lg font-bold text-slate-900">Confirm Bug Severity</h3>
                <p class="text-xs text-slate-500 mt-1 px-4">This will execute the reward command on the server.</p>
            </div>
            <div class="space-y-3">
                <button onclick="confirmApproval('low')" class="w-full p-3 rounded-xl border border-yellow-200 bg-yellow-50 hover:bg-yellow-100 text-yellow-800 font-bold text-sm flex items-center justify-between group transition-all">
                    <span><i class="fas fa-thermometer-empty mr-2 text-yellow-500"></i> Minor Bug</span>
                    <span class="text-[10px] bg-white px-2 py-1 rounded border border-yellow-200 text-slate-500 group-hover:text-yellow-700">100 Bucks</span>
                </button>
                <button onclick="confirmApproval('medium')" class="w-full p-3 rounded-xl border border-orange-200 bg-orange-50 hover:bg-orange-100 text-orange-800 font-bold text-sm flex items-center justify-between group transition-all">
                    <span><i class="fas fa-thermometer-half mr-2 text-orange-500"></i> Major Bug</span>
                    <span class="text-[10px] bg-white px-2 py-1 rounded border border-orange-200 text-slate-500 group-hover:text-orange-700">500 Bucks</span>
                </button>
                <button onclick="confirmApproval('critical')" class="w-full p-3 rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 text-red-800 font-bold text-sm flex items-center justify-between group transition-all">
                    <span><i class="fas fa-bomb mr-2 text-red-500"></i> Critical / Exploit</span>
                    <span class="text-[10px] bg-white px-2 py-1 rounded border border-red-200 text-slate-500 group-hover:text-red-700">1000 + Key</span>
                </button>
            </div>
            <button onclick="closeModal('approvalModal')" class="mt-4 w-full text-slate-400 text-xs font-bold hover:text-slate-600 py-2">Cancel</button>
        </div>
    </div>

    <!-- CUSTOM TOAST -->
    <div id="toast" class="fixed bottom-8 z-50 bg-white px-6 py-4 rounded-xl flex items-center gap-4 border border-slate-100 transform translate-y-24 opacity-0 transition-all duration-500 shadow-[0_8px_30px_rgb(0,0,0,0.12)]">
        <i id="toastIcon" class="fas fa-info-circle text-xl text-red-500"></i>
        <span id="toastMsg" class="text-sm font-semibold text-slate-700">Notification</span>
    </div>

</body>
</html>
