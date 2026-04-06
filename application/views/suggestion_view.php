<?php
$page_title = isset($title) ? (string) $title : 'Suggestion | MineHive';
$page_description = !empty($meta_description)
    ? (string) $meta_description
    : 'Kirim saran, vote ide, dan bantu perkembangan MineHive lewat halaman masukan komunitas.';
$page_image = base_url('assets/images/opengraph_banner.jpg');
?>
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
    <!-- Menggunakan PHP tag sesuai request, pastikan file ini dijalankan di server yang support PHP atau abaikan jika statis -->
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/favicon.png'); ?>">

    <!-- Firebase SDKs -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
        import { getAuth, signInAnonymously, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
        import { getFirestore, collection, addDoc, onSnapshot, doc, deleteDoc, updateDoc } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";

        // --- FIREBASE CONFIG ---
        const firebaseConfig = {
            apiKey: "AIzaSyDCRWCzBpUg3jBUtBtoZZw4XI029SCNkj4",
            authDomain: "minehive-suggestion.firebaseapp.com",
            projectId: "minehive-suggestion",
            storageBucket: "minehive-suggestion.firebasestorage.app",
            messagingSenderId: "649946689048",
            appId: "1:649946689048:web:30b5ef98348673564d9d6d",
            measurementId: "G-B576NKCHK9"
        };        
        const appId = new URLSearchParams(window.location.search).get('__app_id') || 'default-app';
        
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const db = getFirestore(app);

        let currentUser = null;
        let isAdmin = false;
        let unsubscribeSuggestions = null;
        
        // Global variables for filtering
        let globalSuggestions = [];
        let currentFilter = 'All'; // Topic Filter
        let adminGameModeFilter = 'All'; // Game Mode Filter
        let searchQuery = '';
        
        // Form Selection State
        let selectedCategories = new Set(['General']); 
        let selectedGameMode = null; // Forces user to select one

        // --- AUTHENTICATION ---
        signInAnonymously(auth).then(() => {
            console.log("Connected anonymously to Firebase");
        }).catch((error) => {
            console.error("Auth Error:", error);
            showToast("Connection failed", "error");
        });

        onAuthStateChanged(auth, (user) => {
            if (user) {
                currentUser = user;
                const submitBtn = document.getElementById('submitBtn');
                if(submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        });

        // --- APP LOGIC ---

        // 0. Game Mode Selection Logic (UI)
        window.selectGameMode = function(mode) {
            selectedGameMode = mode;
            
            // UI Update for Game Mode Buttons
            const modes = ['Survival', 'Skyblock'];
            modes.forEach(m => {
                const btn = document.getElementById(`btn-${m}`);
                if (m === mode) {
                    // ORANGE THEME
                    btn.classList.add('bg-orange-600', 'text-white', 'border-orange-600', 'shadow-md', 'shadow-orange-200');
                    btn.classList.remove('bg-white', 'text-slate-600', 'border-slate-200', 'hover:border-orange-300');
                } else {
                    btn.classList.remove('bg-orange-600', 'text-white', 'border-orange-600', 'shadow-md', 'shadow-orange-200');
                    btn.classList.add('bg-white', 'text-slate-600', 'border-slate-200', 'hover:border-orange-300');
                }
            });
        };

        // 0.1 Category Selection Logic (UI)
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
                    // ORANGE THEME
                    btn.classList.add('bg-orange-100', 'border-orange-500', 'text-orange-700', 'ring-2', 'ring-orange-500/20');
                    btn.classList.remove('bg-slate-50', 'border-slate-200', 'text-slate-600');
                } else {
                    btn.classList.remove('bg-orange-100', 'border-orange-500', 'text-orange-700', 'ring-2', 'ring-orange-500/20');
                    btn.classList.add('bg-slate-50', 'border-slate-200', 'text-slate-600');
                }
            });
        }

        // 1. Submit Suggestion
        window.submitSuggestion = async function(e) {
            e.preventDefault();
            
            if (!currentUser) {
                showToast("Connecting to server...", "warning");
                return;
            }

            const message = document.getElementById('message').value;
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');

            // Validation
            if (!selectedGameMode) {
                showToast("Please select a Game Mode!", "warning");
                // Shake animation for game mode section
                const gmSection = document.getElementById('gameModeSection');
                gmSection.classList.add('shake');
                setTimeout(() => gmSection.classList.remove('shake'), 500);
                return;
            }

            if (!message.trim()) {
                showToast("Please write something!", "error");
                return;
            }

            btn.disabled = true;
            btnText.textContent = "Sending...";
            btnIcon.classList.add('animate-spin');
            btnIcon.classList.replace('fa-paper-plane', 'fa-circle-notch');

            try {
                const colRef = collection(db, 'artifacts', appId, 'public', 'data', 'suggestions');
                const categoriesArray = Array.from(selectedCategories);

                await addDoc(colRef, {
                    message: message,
                    categories: categoriesArray,
                    gameMode: selectedGameMode, // Save Game Mode
                    status: 'pending', 
                    createdAt: Date.now(), 
                    userId: currentUser.uid 
                });

                showToast("Feedback sent successfully!", "success");
                document.getElementById('suggestionForm').reset();
                updateCharCount(document.getElementById('message'));
                
                // Reset Selection
                selectedCategories = new Set(['General']);
                updateCategoryUI();
                
                // Reset Game Mode
                selectedGameMode = null;
                document.querySelectorAll('.gamemode-btn').forEach(b => {
                    b.classList.remove('bg-orange-600', 'text-white', 'border-orange-600', 'shadow-md', 'shadow-orange-200');
                    b.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
                });

            } catch (error) {
                console.error("Error adding document: ", error);
                showToast("Failed to send. Try again.", "error");
            } finally {
                btn.disabled = false;
                btnText.textContent = "Send Feedback";
                btnIcon.classList.remove('animate-spin');
                btnIcon.classList.replace('fa-circle-notch', 'fa-paper-plane');
            }
        };

        // 2. Admin Login
        window.toggleAdmin = function() {
            const adminPanel = document.getElementById('adminPanel');
            const userPanel = document.getElementById('userPanel');
            const mainHeader = document.getElementById('mainHeader');
            const modal = document.getElementById('loginModal');

            if (isAdmin) {
                isAdmin = false;
                if (unsubscribeSuggestions) unsubscribeSuggestions();
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
                loadSuggestions();
            } else {
                showToast("Invalid Password!", "error");
                document.getElementById('adminPass').classList.add('shake');
                setTimeout(() => document.getElementById('adminPass').classList.remove('shake'), 500);
            }
        };

        window.closeModal = function() {
            document.getElementById('loginModal').classList.add('hidden');
        };

        // 3. Load Data & Filter
        function loadSuggestions() {
            if (!currentUser) return;
            const colRef = collection(db, 'artifacts', appId, 'public', 'data', 'suggestions');

            unsubscribeSuggestions = onSnapshot(colRef, (snapshot) => {
                globalSuggestions = [];
                snapshot.forEach(doc => {
                    globalSuggestions.push({ id: doc.id, ...doc.data() });
                });
                globalSuggestions.sort((a, b) => b.createdAt - a.createdAt);
                renderSuggestions();
            }, (error) => {
                console.error("Error fetching suggestions:", error);
                showToast("Failed to load data", "error");
            });
        }

        // Filter by Topic (Categories)
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
            renderSuggestions();
        }

        // Filter by Game Mode
        window.setGameModeFilter = function(mode) {
            adminGameModeFilter = mode;
            document.querySelectorAll('.gm-filter-btn').forEach(btn => {
                if(btn.dataset.mode === mode) {
                    // ORANGE THEME
                    btn.classList.add('bg-orange-600', 'text-white', 'border-orange-600');
                    btn.classList.remove('bg-slate-800', 'text-slate-400', 'border-slate-700');
                } else {
                    btn.classList.remove('bg-orange-600', 'text-white', 'border-orange-600');
                    btn.classList.add('bg-slate-800', 'text-slate-400', 'border-slate-700');
                }
            });
            renderSuggestions();
        }

        window.handleSearch = function(e) {
            searchQuery = e.target.value.toLowerCase();
            renderSuggestions();
        }

        // Helper to get category style
        function getCategoryStyle(cat) {
            if(cat === 'Economy') return { class: 'bg-green-100 text-green-700 border-green-200', icon: 'fa-coins' };
            if(cat === 'Buff/Nerf') return { class: 'bg-red-100 text-red-700 border-red-200', icon: 'fa-balance-scale' };
            if(cat === 'Bug Report') return { class: 'bg-amber-100 text-amber-700 border-amber-200', icon: 'fa-bug' };
            if(cat === 'Events') return { class: 'bg-purple-100 text-purple-700 border-purple-200', icon: 'fa-calendar-star' };
            return { class: 'bg-blue-100 text-blue-700 border-blue-200', icon: 'fa-comments' }; // General default
        }

        function renderSuggestions() {
            const listContainer = document.getElementById('suggestionList');
            const msgCount = document.getElementById('msgCount');
            
            // Filter Logic (Category + GameMode + Search)
            let displayList = globalSuggestions.filter(item => {
                const itemCats = item.categories || (item.category ? [item.category] : ['General']);
                const matchesTopic = currentFilter === 'All' || itemCats.includes(currentFilter);
                
                const itemGM = item.gameMode || 'Survival'; 
                const matchesGM = adminGameModeFilter === 'All' || itemGM === adminGameModeFilter;

                const msg = (item.message || '').toLowerCase();
                const matchesSearch = msg.includes(searchQuery);

                return matchesTopic && matchesGM && matchesSearch;
            });

            if(msgCount) msgCount.innerText = displayList.length;

            listContainer.innerHTML = '';
            
            if (displayList.length === 0) {
                listContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-20 text-slate-400">
                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-search text-3xl text-slate-300"></i>
                        </div>
                        <p class="font-medium text-slate-500">No suggestions found</p>
                    </div>
                `;
                return;
            }

            displayList.forEach(item => {
                const dateObj = new Date(item.createdAt);
                const date = dateObj.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
                const time = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                
                const itemCats = item.categories || (item.category ? [item.category] : ['General']);
                const itemGM = item.gameMode || 'Survival';
                
                let gmIcon = itemGM === 'Skyblock' ? 'fa-cloud' : 'fa-tree';
                let gmColor = itemGM === 'Skyblock' ? 'text-sky-600 bg-sky-50 border-sky-200' : 'text-emerald-600 bg-emerald-50 border-emerald-200';

                const tagsHtml = itemCats.map(cat => {
                    const style = getCategoryStyle(cat);
                    return `
                        <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide border ${style.class} inline-flex items-center">
                            <i class="fas ${style.icon} mr-1"></i> ${cat}
                        </span>
                    `;
                }).join('');

                const status = item.status || 'pending';
                let statusBorder = 'border-slate-100';
                let statusBg = 'bg-white';
                let opacity = 'opacity-100';
                
                if(status === 'approved') {
                    statusBorder = 'border-green-500 border-l-4';
                    statusBg = 'bg-green-50/30';
                } else if (status === 'rejected') {
                    statusBg = 'bg-slate-50';
                    opacity = 'opacity-60 grayscale-[0.8]';
                }

                const card = document.createElement('div');
                card.className = `group ${statusBg} ${statusBorder} ${opacity} p-5 rounded-xl border shadow-sm hover:shadow-md transition-all duration-300 relative`;
                
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-bold border border-slate-200">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Anonymous</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase border ${gmColor} flex items-center gap-1">
                                        <i class="fas ${gmIcon}"></i> ${itemGM}
                                    </span>
                                </div>
                                <span class="text-xs text-slate-500 font-medium">${date} • ${time}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1 justify-end max-w-[50%]">
                             ${tagsHtml}
                        </div>
                    </div>
                    
                    <div class="pl-11 mb-4">
                        <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-wrap">${item.message}</p>
                    </div>

                    <!-- Admin Actions -->
                    <div class="pl-11 pt-3 border-t border-slate-100 flex justify-between items-center">
                        <div class="flex gap-2">
                            <button onclick="updateStatus('${item.id}', 'approved')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${status === 'approved' ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'bg-white border border-slate-200 text-slate-500 hover:border-green-500 hover:text-green-600'}">
                                <i class="fas fa-check mr-1"></i> Approve
                            </button>
                            <button onclick="updateStatus('${item.id}', 'rejected')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${status === 'rejected' ? 'bg-red-600 text-white shadow-lg shadow-red-200' : 'bg-white border border-slate-200 text-slate-500 hover:border-red-500 hover:text-red-600'}">
                                <i class="fas fa-times mr-1"></i> Reject
                            </button>
                        </div>
                        <button onclick="deleteSuggestion('${item.id}')" class="text-slate-300 hover:text-red-500 p-2 rounded-lg transition-all" title="Delete Permanent">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                `;
                listContainer.appendChild(card);
            });
        }

        window.updateStatus = async function(docId, newStatus) {
            try {
                const docRef = doc(db, 'artifacts', appId, 'public', 'data', 'suggestions', docId);
                await updateDoc(docRef, { status: newStatus });
            } catch (err) {
                console.error(err);
                showToast("Failed to update status", "error");
            }
        };

        window.deleteSuggestion = async function(docId) {
            if(!confirm('Delete this feedback permanently?')) return;
            try {
                const docRef = doc(db, 'artifacts', appId, 'public', 'data', 'suggestions', docId);
                await deleteDoc(docRef);
                showToast("Deleted successfully", "success");
            } catch (err) {
                showToast("Failed to delete", "error");
            }
        };

        // --- UTILITIES ---
        window.updateCharCount = function(textarea) {
            const count = textarea.value.length;
            const counter = document.getElementById('charCount');
            counter.innerText = `${count} / 1000`;
            if(count >= 1000) {
                counter.classList.add('text-red-500', 'font-bold');
            } else {
                counter.classList.remove('text-red-500', 'font-bold');
            }
        };

        window.showToast = function(msg, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toastMsg');
            const toastIcon = document.getElementById('toastIcon');
            
            toastMsg.innerText = msg;
            
            if(type === 'success') {
                toastIcon.className = 'fas fa-check-circle text-green-500 text-xl';
            } else if (type === 'error') {
                toastIcon.className = 'fas fa-exclamation-circle text-red-500 text-xl';
            } else if (type === 'warning') {
                toastIcon.className = 'fas fa-exclamation-triangle text-yellow-500 text-xl';
            } else {
                toastIcon.className = 'fas fa-info-circle text-blue-500 text-xl';
            }

            toast.classList.remove('translate-y-24', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0');
            }, 3000);
        };
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff7ed; /* Orange-50 */
            color: #1e293b;
        }

        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
        .animate-slide-up { animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(15px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        .shake { animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-3px, 0, 0); }
            40%, 60% { transform: translate3d(3px, 0, 0); }
        }

        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #fed7aa; border-radius: 20px; } /* Orange scrollbar */
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #fdba74; }
        
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        textarea:focus, input:focus { box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2); } /* Orange focus */
    </style>
</head>
<!-- UPDATED BODY CLASS: Removed overflow-hidden, Added overflow-y-auto and py-10 for mobile scrolling -->
<body class="min-h-screen flex flex-col items-center justify-center p-6 py-10 bg-orange-50 relative overflow-y-auto overflow-x-hidden">

    <!-- Decorative background blobs - Updated for Orange Theme -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-orange-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70"></div>
        <div class="absolute top-[-10%] right-[-10%] w-96 h-96 bg-yellow-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-red-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70"></div>
    </div>

    <!-- MAIN CONTAINER -->
    <main class="w-full max-w-lg relative z-10 transition-all duration-500 my-auto">
        
        <!-- HEADER (Visible for User) -->
        <div id="mainHeader" class="text-center mb-10 animate-slide-up">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white shadow-lg shadow-orange-500/10 mb-6 text-orange-600">
                <i class="fas fa-comment-alt text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 mb-2">
                Server Feedback
            </h1>
            <p class="text-slate-500 text-sm">
                Share your thoughts with the admins anonymously.
            </p>
        </div>

        <!-- USER PANEL (FORM) -->
        <div id="userPanel" class="bg-white rounded-3xl p-8 shadow-2xl shadow-slate-200/50 animate-slide-up border border-slate-100" style="animation-delay: 0.1s">
            
            <form id="suggestionForm" onsubmit="submitSuggestion(event)" class="space-y-6">
                
                <!-- NEW: Game Mode Selector -->
                <div id="gameModeSection" class="space-y-2">
                    <label class="text-xs uppercase tracking-wider text-slate-500 font-bold ml-1">Game Mode <span class="text-red-400">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" id="btn-Survival" onclick="selectGameMode('Survival')" class="gamemode-btn w-full py-3 rounded-xl border border-slate-200 bg-white text-slate-600 font-bold text-sm transition-all flex items-center justify-center gap-2 hover:border-orange-300">
                            <i class="fas fa-tree"></i> Survival
                        </button>
                        <button type="button" id="btn-Skyblock" onclick="selectGameMode('Skyblock')" class="gamemode-btn w-full py-3 rounded-xl border border-slate-200 bg-white text-slate-600 font-bold text-sm transition-all flex items-center justify-center gap-2 hover:border-orange-300">
                            <i class="fas fa-cloud"></i> Skyblock
                        </button>
                    </div>
                </div>

                <!-- Category Selector -->
                <div class="space-y-2 w-full">
                    <label class="text-xs uppercase tracking-wider text-slate-500 font-bold ml-1">Topics (Multi-select)</label>
                    <!-- Scroll Fix: w-full added to ensure constraints -->
                    <div class="flex gap-2 overflow-x-auto pb-2 w-full no-scrollbar snap-x">
                        <button type="button" data-val="General" onclick="toggleCategory('General')" class="cat-btn flex-shrink-0 snap-start px-4 py-2 rounded-lg text-xs font-bold border transition-all bg-orange-100 border-orange-500 text-orange-700 ring-2 ring-orange-500/20">
                            General
                        </button>
                        <button type="button" data-val="Economy" onclick="toggleCategory('Economy')" class="cat-btn flex-shrink-0 snap-start px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-600 bg-slate-50 hover:bg-slate-100 transition-all">
                            Economy
                        </button>
                        <button type="button" data-val="Buff/Nerf" onclick="toggleCategory('Buff/Nerf')" class="cat-btn flex-shrink-0 snap-start px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-600 bg-slate-50 hover:bg-slate-100 transition-all">
                            Buff / Nerf
                        </button>
                        <button type="button" data-val="Bug Report" onclick="toggleCategory('Bug Report')" class="cat-btn flex-shrink-0 snap-start px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-600 bg-slate-50 hover:bg-slate-100 transition-all">
                            Bug Report
                        </button>
                        <button type="button" data-val="Events" onclick="toggleCategory('Events')" class="cat-btn flex-shrink-0 snap-start px-4 py-2 rounded-lg text-xs font-bold border border-slate-200 text-slate-600 bg-slate-50 hover:bg-slate-100 transition-all">
                            Events
                        </button>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="space-y-2">
                    <label class="text-xs uppercase tracking-wider text-slate-500 font-bold ml-1">Your Message</label>
                    <div class="relative">
                        <textarea 
                            id="message" 
                            rows="5" 
                            maxlength="1000"
                            oninput="updateCharCount(this)"
                            class="w-full bg-slate-50 text-slate-800 border border-slate-200 rounded-xl px-5 py-4 focus:outline-none focus:bg-white focus:border-orange-500 transition-all resize-none placeholder-slate-400 text-sm"
                            placeholder="I think we should nerf the Void Sword because..."></textarea>
                    </div>
                    <div class="flex justify-end">
                        <span id="charCount" class="text-xs text-slate-400 font-medium transition-colors">0 / 1000</span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn" disabled class="w-full py-4 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold shadow-lg shadow-orange-500/30 transform active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="btnText">Send Feedback</span>
                    <i id="btnIcon" class="fas fa-paper-plane text-sm"></i>
                </button>

                <div class="text-center pt-2">
                    <p class="text-[11px] text-slate-400 flex items-center justify-center gap-1.5 font-medium">
                        <i class="fas fa-lock text-slate-300"></i> Secure & Anonymous
                    </p>
                </div>
            </form>
        </div>

        <!-- ADMIN PANEL -->
        <div id="adminPanel" class="bg-white rounded-3xl shadow-2xl shadow-slate-200/50 hidden relative flex flex-col border border-slate-100 overflow-hidden h-[700px]">
            
            <!-- Admin Header -->
            <div class="bg-slate-900 p-6 z-10 shrink-0 space-y-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/50">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h2 class="text-white font-bold text-lg leading-tight">Admin Console</h2>
                            <p class="text-slate-400 text-xs">Manage Feedback</p>
                        </div>
                    </div>
                    <button onclick="toggleAdmin()" class="group flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 transition-colors border border-slate-700">
                        <span class="text-xs font-medium text-slate-300 group-hover:text-white">Logout</span>
                        <i class="fas fa-sign-out-alt text-slate-400 group-hover:text-white text-xs"></i>
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="relative">
                    <input type="text" 
                        oninput="handleSearch(event)"
                        placeholder="Search feedback..." 
                        class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-orange-500 transition-colors placeholder-slate-500">
                    <i class="fas fa-search absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-slate-900 px-4 pb-4 border-b border-slate-800 shrink-0 space-y-3">
                
                <!-- Game Mode Filter -->
                <div class="flex gap-2 justify-center pb-2 border-b border-slate-800/50">
                    <button onclick="setGameModeFilter('All')" data-mode="All" class="gm-filter-btn px-4 py-1.5 rounded-lg text-xs font-bold transition-all bg-orange-600 text-white border border-orange-600">All Modes</button>
                    <button onclick="setGameModeFilter('Survival')" data-mode="Survival" class="gm-filter-btn px-4 py-1.5 rounded-lg text-xs font-bold transition-all bg-slate-800 text-slate-400 border border-slate-700 hover:text-white">Survival</button>
                    <button onclick="setGameModeFilter('Skyblock')" data-mode="Skyblock" class="gm-filter-btn px-4 py-1.5 rounded-lg text-xs font-bold transition-all bg-slate-800 text-slate-400 border border-slate-700 hover:text-white">Skyblock</button>
                </div>

                <!-- Topic Filters -->
                 <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
                     <button onclick="setFilter('All')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-slate-800 text-white" data-filter="All">All Topics</button>
                     <button onclick="setFilter('Economy')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-400 hover:text-white" data-filter="Economy">Economy</button>
                     <button onclick="setFilter('Buff/Nerf')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-400 hover:text-white" data-filter="Buff/Nerf">Buff/Nerf</button>
                     <button onclick="setFilter('Bug Report')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-400 hover:text-white" data-filter="Bug Report">Bugs</button>
                     <button onclick="setFilter('Events')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-400 hover:text-white" data-filter="Events">Events</button>
                     <button onclick="setFilter('General')" class="filter-tab whitespace-nowrap px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-400 hover:text-white" data-filter="General">General</button>
                 </div>
            </div>

            <!-- Stats Bar -->
            <div class="bg-slate-50 border-b border-slate-200 px-6 py-3 flex justify-between items-center shrink-0">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Inbox</span>
                <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2.5 py-0.5 rounded-full border border-orange-200">
                    <span id="msgCount">0</span> Messages
                </span>
            </div>

            <!-- List of Suggestions -->
            <div id="suggestionList" class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar bg-slate-50/50">
                <!-- Loading State -->
                <div class="flex flex-col items-center justify-center h-full text-slate-400 animate-pulse">
                    <i class="fas fa-circle-notch fa-spin text-2xl mb-2 text-orange-300"></i>
                    <p class="text-xs font-medium">Syncing data...</p>
                </div>
            </div>
        </div>

        <!-- Admin Toggle Trigger (Footer) -->
        <div class="mt-8 flex justify-center opacity-30 hover:opacity-100 transition-opacity">
            <button onclick="toggleAdmin()" class="text-slate-400 hover:text-orange-500 transition-colors p-2" title="Admin Login">
                <i class="fas fa-fingerprint text-lg"></i>
            </button>
        </div>

    </main>

    <!-- ADMIN LOGIN MODAL -->
    <div id="loginModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden animate-fade-in p-4">
        <div class="bg-white w-full max-w-sm rounded-2xl p-8 shadow-2xl transform transition-all scale-100 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-orange-500 via-red-500 to-yellow-500"></div>

            <div class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Admin Access</h3>
                    <p class="text-slate-500 text-xs mt-1">Enter passcode to view dashboard</p>
                </div>
                <button onclick="closeModal()" class="text-slate-300 hover:text-slate-500 bg-slate-50 p-2 rounded-full w-8 h-8 flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="relative">
                <input type="password" id="adminPass" 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 pl-10 text-slate-800 mb-4 focus:outline-none focus:border-orange-500 focus:bg-white transition-colors font-bold text-lg placeholder:font-normal placeholder:text-slate-400"
                    placeholder="Passcode..."
                    onkeypress="if(event.key === 'Enter') checkPassword()">
                <i class="fas fa-key absolute left-4 top-4 text-slate-400"></i>
            </div>
            
            <button onclick="checkPassword()" class="w-full bg-slate-900 text-white font-semibold py-3.5 rounded-xl hover:bg-slate-800 transition-colors shadow-lg shadow-slate-900/20 flex items-center justify-center gap-2">
                <span>Unlock Dashboard</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </button>
        </div>
    </div>

    <!-- CUSTOM TOAST -->
    <div id="toast" class="fixed bottom-8 z-50 bg-white px-6 py-4 rounded-xl flex items-center gap-4 border border-slate-100 transform translate-y-24 opacity-0 transition-all duration-500 shadow-[0_8px_30px_rgb(0,0,0,0.12)]">
        <i id="toastIcon" class="fas fa-info-circle text-xl text-orange-500"></i>
        <span id="toastMsg" class="text-sm font-semibold text-slate-700">Notification</span>
    </div>

</body>
</html>
