<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Números - TIM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        timBlue: '#004C97',
                        timRed: '#E30613',
                        timYellow: '#FFD100',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #004C97 0%, #003366 100%);
            background-attachment: fixed;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 16px 0 rgba(0, 0, 0, 0.15);
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.15);
        }
        
        .progress-gradient {
            background: linear-gradient(90deg, #004C97, #E30613);
            background-size: 200% 100%;
            animation: gradient-shift 3s ease infinite;
        }
        
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .tab-indicator {
            position: relative;
        }
        
        .tab-indicator::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: #E30613;
            transition: width 0.3s ease;
        }
        
        .tab-indicator.active::after {
            width: 100%;
        }
        
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(0, 76, 151, 0.1);
        }
        
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>
</head>
<body class="min-h-screen py-4 px-4 md:px-6">
    <!-- Header Compacto -->
    <div class="max-w-5xl mx-auto mb-4">
        <div class="glass-effect rounded-xl p-4 text-center card-hover">
            <div class="flex items-center justify-center gap-4 mb-3">
                <!-- Logo TIM -->
                <div class="flex items-center">
                    <img src="TIM.png" alt="TIM" class="h-12 md:h-16 object-contain">
                </div>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-[#004C97] mb-1">
                Consulta de Números
            </h1>
            <p class="text-gray-600 text-sm">Consulte informações de números telefônicos</p>
        </div>
    </div>

    <!-- Main Container -->
    <div class="max-w-5xl mx-auto">
        <!-- Tabs Navigation Compacta -->
        <div class="glass-effect rounded-t-xl overflow-hidden mb-3">
            <div class="flex border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                <button class="tab-indicator active flex-1 py-3 px-4 text-center cursor-pointer bg-transparent border-none text-sm font-semibold text-[#004C97] transition-all duration-300 relative" data-tab="individual">
                    <i class="fas fa-search mr-2"></i>
                    Consulta Individual
                </button>
                <button class="tab-indicator flex-1 py-3 px-4 text-center cursor-pointer bg-transparent border-none text-sm font-semibold text-gray-600 transition-all duration-300 relative hover:text-[#004C97]" data-tab="batch">
                    <i class="fas fa-file-upload mr-2"></i>
                    Consulta em Lote
                </button>
            </div>
        </div>

        <!-- Tab Content Container Compacto -->
        <div class="glass-effect rounded-xl p-6 md:p-8 shadow-xl">
            <!-- Aba Consulta Individual -->
            <div id="tab-individual" class="tab-content block">
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-1 flex items-center">
                        <i class="fas fa-keyboard mr-2 text-[#004C97]"></i>
                        Digite os Números
                    </h2>
                    <p class="text-gray-600 text-sm">Insira um ou mais números para consulta rápida</p>
                </div>

                <form id="individualForm" class="space-y-4">
                    <div>
                        <label for="numbersInput" class="block mb-2 text-gray-700 font-semibold text-xs uppercase tracking-wide">
                            <i class="fas fa-list-ol mr-2 text-[#004C97]"></i>
                            Números para Consulta
                        </label>
                        <div class="relative">
                            <textarea 
                                id="numbersInput" 
                                name="numbers" 
                                placeholder="Digite os números separados por vírgula ou um por linha&#10;&#10;Exemplo:&#10;11941900123,81981562716&#10;&#10;ou&#10;&#10;11941900123&#10;81981562716" 
                                required
                                class="input-focus w-full min-h-[120px] p-3 border-2 border-gray-300 rounded-lg text-sm resize-y transition-all duration-300 focus:outline-none focus:border-[#004C97] bg-white"
                            ></textarea>
                            <div class="absolute bottom-2 right-2 text-xs text-gray-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                Máx. 100 números
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 flex items-center">
                            <i class="fas fa-lightbulb mr-1 text-[#FFD100]"></i>
                            Você pode digitar múltiplos números separados por vírgula ou um por linha
                        </p>
                    </div>
                    
                    <button type="submit" class="btn-submit w-full py-3 bg-[#004C97] text-white border-none rounded-lg text-sm font-semibold cursor-pointer transition-all duration-300 hover:bg-[#003366] disabled:bg-gray-400 disabled:cursor-not-allowed shadow-md hover:shadow-lg transform hover:scale-[1.01]" id="individualSubmitBtn">
                        <i class="fas fa-search mr-2"></i>
                        Consultar Números
                    </button>
                </form>
                
                <div class="status-message mt-4 p-3 rounded-lg hidden shadow-sm" id="individualStatusMessage"></div>
                
                <div class="text-center py-4 hidden" id="individualLoading">
                    <div class="inline-flex items-center space-x-2 text-[#004C97]">
                        <div class="pulse-animation">
                            <i class="fas fa-spinner fa-spin text-xl"></i>
                        </div>
                        <span class="font-semibold text-sm">Consultando números...</span>
                    </div>
                </div>
                
                <div class="results-container mt-4 hidden" id="individualResults">
                    <div class="bg-[#004C97]/10 rounded-lg p-3 mb-3 border-l-4 border-[#004C97]">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center">
                            <i class="fas fa-table mr-2 text-[#004C97]"></i>
                            Resultados da Consulta
                        </h3>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-inner">
                        <table class="w-full border-collapse text-xs bg-white" id="individualResultsTable">
                            <thead>
                                <tr class="bg-[#004C97]">
                                    <th class="p-2 text-left text-white font-semibold sticky top-0">Número</th>
                                    <th class="p-2 text-left text-white font-semibold sticky top-0">Tipo</th>
                                    <th class="p-2 text-left text-white font-semibold sticky top-0">EOT</th>
                                    <th class="p-2 text-left text-white font-semibold sticky top-0">Holding</th>
                                    <th class="p-2 text-left text-white font-semibold sticky top-0">Operadora</th>
                                    <th class="p-2 text-left text-white font-semibold sticky top-0">CNL</th>
                                    <th class="p-2 text-left text-white font-semibold sticky top-0">Localidade</th>
                                    <th class="p-2 text-left text-white font-semibold sticky top-0">Portado</th>
                                    <th class="p-2 text-left text-white font-semibold sticky top-0">Status</th>
                                </tr>
                            </thead>
                            <tbody id="individualResultsBody" class="divide-y divide-gray-200">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Aba Consulta em Lote -->
            <div id="tab-batch" class="tab-content hidden">
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-1 flex items-center">
                        <i class="fas fa-cloud-upload-alt mr-2 text-[#004C97]"></i>
                        Upload de Arquivo
                    </h2>
                    <p class="text-gray-600 text-sm">Envie um arquivo CSV ou TXT com múltiplos números</p>
                </div>

                <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
                    <div class="upload-area border-2 border-dashed border-[#004C97]/50 rounded-xl p-8 text-center bg-[#004C97]/5 transition-all duration-300 cursor-pointer hover:border-[#004C97] hover:bg-[#004C97]/10 card-hover" id="uploadArea">
                        <div class="mb-4">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-[#004C97] rounded-full mb-3 shadow-md">
                                <i class="fas fa-cloud-upload-alt text-white text-2xl"></i>
                            </div>
                            <p class="text-lg font-semibold text-gray-700 mb-1">
                                Arraste e solte o arquivo aqui
                            </p>
                            <p class="text-gray-500 text-sm mb-3">ou</p>
                            <button type="button" id="selectFileBtn" class="inline-flex items-center px-6 py-2 bg-[#004C97] text-white rounded-lg cursor-pointer transition-all duration-300 hover:bg-[#003366] shadow-md hover:shadow-lg transform hover:scale-105 border-none">
                                <i class="fas fa-folder-open mr-2"></i>
                                Selecionar Arquivo
                            </button>
                        </div>
                        <input type="file" id="fileInput" name="file" accept=".csv,.txt" class="hidden">
                        <div class="file-info mt-4 p-3 bg-white rounded-lg shadow-sm hidden border-l-4 border-[#004C97]" id="fileInfo">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-600 mb-1">
                                        <i class="fas fa-file mr-2 text-[#004C97]"></i>
                                        <strong>Arquivo:</strong> <span id="fileName" class="font-semibold text-gray-800"></span>
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        <i class="fas fa-weight mr-2 text-[#004C97]"></i>
                                        <strong>Tamanho:</strong> <span id="fileSize" class="font-semibold text-gray-800"></span>
                                    </p>
                                </div>
                                <div class="text-green-500">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit w-full py-3 bg-[#004C97] text-white border-none rounded-lg text-sm font-semibold cursor-pointer transition-all duration-300 hover:bg-[#003366] disabled:bg-gray-400 disabled:cursor-not-allowed shadow-md hover:shadow-lg transform hover:scale-[1.01]" id="submitBtn">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Enviar e Processar
                    </button>
                </form>
                
                <div class="mt-4">
                    <a href="historico.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-history mr-2"></i>
                        Ver Histórico de Consultas
                    </a>
                </div>
                
                <div class="progress-container mt-4 p-4 bg-gray-50 rounded-lg hidden" id="progressContainer">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-semibold text-gray-700 text-sm">
                            <i class="fas fa-tasks mr-2 text-[#004C97]"></i>
                            Status: <span id="statusText" class="text-[#004C97]">Processando...</span>
                        </p>
                    </div>
                    <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden shadow-inner">
                        <div class="progress-fill h-full w-0 transition-all duration-300 flex items-center justify-center text-white text-xs font-bold" id="progressFill">0%</div>
                    </div>
                </div>
                
                <div class="status-message mt-4 p-3 rounded-lg hidden shadow-sm" id="statusMessage"></div>
                
                <div class="format-info mt-4 p-4 bg-gradient-to-r from-amber-50 to-yellow-50 rounded-lg border-l-4 border-amber-400 shadow-sm">
                    <h3 class="text-amber-800 mb-2 text-sm font-bold flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        Formato do Arquivo
                    </h3>
                    <ul class="ml-5 text-amber-800 text-xs space-y-1 list-disc">
                        <li>Arquivo CSV ou TXT com um número por linha</li>
                        <li>Números podem estar separados por vírgula ou um por linha</li>
                        <li>Tamanho máximo: 10MB</li>
                        <li>Exemplo: <code class="bg-amber-100 px-1 py-0.5 rounded">11941900123,81981562716</code> ou um número por linha</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.tab-indicator').forEach(tab => {
            tab.addEventListener('click', () => {
                const targetTab = tab.dataset.tab;
                
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab-indicator').forEach(t => {
                    t.classList.remove('active', 'text-[#004C97]', 'bg-white');
                    t.classList.add('text-gray-600');
                });
                document.querySelectorAll('.tab-content').forEach(c => {
                    c.classList.add('hidden');
                    c.classList.remove('block');
                });
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active', 'text-[#004C97]', 'bg-white');
                tab.classList.remove('text-gray-600');
                const content = document.getElementById(`tab-${targetTab}`);
                content.classList.remove('hidden');
                content.classList.add('block');
            });
        });
        
        // ========== CONSULTA INDIVIDUAL ==========
        const individualForm = document.getElementById('individualForm');
        const individualSubmitBtn = document.getElementById('individualSubmitBtn');
        const individualStatusMessage = document.getElementById('individualStatusMessage');
        const individualLoading = document.getElementById('individualLoading');
        const individualResults = document.getElementById('individualResults');
        const individualResultsBody = document.getElementById('individualResultsBody');
        
        individualForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const numbersInput = document.getElementById('numbersInput').value.trim();
            if (!numbersInput) {
                showIndividualMessage('Por favor, digite pelo menos um número', 'error');
                return;
            }
            
            individualSubmitBtn.disabled = true;
            individualLoading.classList.remove('hidden');
            individualResults.classList.add('hidden');
            individualStatusMessage.classList.add('hidden');
            
            try {
                const formData = new FormData();
                formData.append('numbers', numbersInput);
                
                const response = await fetch('consult.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showIndividualMessage(`<i class="fas fa-check-circle mr-2"></i>Consulta realizada com sucesso! ${result.data.length} número(s) consultado(s).`, 'success');
                    displayIndividualResults(result.data);
                } else {
                    showIndividualMessage(`<i class="fas fa-exclamation-circle mr-2"></i>${result.message || 'Erro ao consultar números'}`, 'error');
                }
            } catch (error) {
                showIndividualMessage(`<i class="fas fa-exclamation-triangle mr-2"></i>Erro ao consultar números: ${error.message}`, 'error');
            } finally {
                individualSubmitBtn.disabled = false;
                individualLoading.classList.add('hidden');
            }
        });
        
        function showIndividualMessage(message, type) {
            individualStatusMessage.innerHTML = message;
            individualStatusMessage.className = 'status-message mt-4 p-3 rounded-lg block shadow-sm';
            
            if (type === 'success') {
                individualStatusMessage.classList.add('bg-green-50', 'text-green-800', 'border-l-4', 'border-green-500');
            } else if (type === 'error') {
                individualStatusMessage.classList.add('bg-red-50', 'text-red-800', 'border-l-4', 'border-red-500');
            } else {
                individualStatusMessage.classList.add('bg-blue-50', 'text-blue-800', 'border-l-4', 'border-blue-500');
            }
        }
        
        function displayIndividualResults(data) {
            individualResultsBody.innerHTML = '';
            
            if (!data || data.length === 0) {
                individualResultsBody.innerHTML = '<tr><td colspan="9" class="text-center py-6 text-gray-500"><i class="fas fa-inbox text-2xl mb-2 block"></i>Nenhum resultado encontrado</td></tr>';
                individualResults.classList.remove('hidden');
                return;
            }
            
            data.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-[#004C97]/5 transition-colors';
                const errorClass = item.erro ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold';
                const errorIcon = item.erro ? '<i class="fas fa-times-circle mr-1"></i>' : '<i class="fas fa-check-circle mr-1"></i>';
                row.innerHTML = `
                    <td class="p-2 text-left border-b border-gray-200 text-gray-800 font-medium">${escapeHtml(item.numero || '')}</td>
                    <td class="p-2 text-left border-b border-gray-200 text-gray-700">${escapeHtml(item.TipoPrefixo || '')}</td>
                    <td class="p-2 text-left border-b border-gray-200 text-gray-700">${escapeHtml(item.eot || '')}</td>
                    <td class="p-2 text-left border-b border-gray-200 text-gray-700">${escapeHtml(item.holding || '')}</td>
                    <td class="p-2 text-left border-b border-gray-200 text-gray-700">${escapeHtml(item.operadora || '')}</td>
                    <td class="p-2 text-left border-b border-gray-200 text-gray-700">${escapeHtml(item.cnl || '')}</td>
                    <td class="p-2 text-left border-b border-gray-200 text-gray-700">${escapeHtml(item.localidade || '')}</td>
                    <td class="p-2 text-left border-b border-gray-200 text-gray-700">${escapeHtml(item.portado || '')}</td>
                    <td class="p-2 text-left border-b border-gray-200 ${errorClass}">${errorIcon}${escapeHtml(item.erro || 'OK')}</td>
                `;
                individualResultsBody.appendChild(row);
            });
            
            individualResults.classList.remove('hidden');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // ========== CONSULTA EM LOTE ==========
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');
        const progressContainer = document.getElementById('progressContainer');
        const progressFill = document.getElementById('progressFill');
        const statusText = document.getElementById('statusText');
        const statusMessage = document.getElementById('statusMessage');
        
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('border-[#004C97]', 'bg-[#004C97]/10', 'scale-105');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('border-[#004C97]', 'bg-[#004C97]/10', 'scale-105');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('border-[#004C97]', 'bg-[#004C97]/10', 'scale-105');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect();
            }
        });
        
        // Flag para evitar múltiplos cliques
        let isSelectingFile = false;
        
        // Botão de seleção de arquivo
        const selectFileBtn = document.getElementById('selectFileBtn');
        if (selectFileBtn) {
            selectFileBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!isSelectingFile) {
                    isSelectingFile = true;
                    fileInput.click();
                    setTimeout(() => { isSelectingFile = false; }, 300);
                }
            });
        }
        
        // Área de upload também pode abrir seletor (mas não quando clicar no botão)
        uploadArea.addEventListener('click', (e) => {
            // Não abrir seletor se clicar no botão ou no fileInfo
            if (e.target.closest('#selectFileBtn') || e.target.closest('#fileInfo')) {
                return;
            }
            if (!isSelectingFile) {
                isSelectingFile = true;
                fileInput.click();
                setTimeout(() => { isSelectingFile = false; }, 300);
            }
        });
        
        fileInput.addEventListener('change', function(e) {
            e.stopPropagation();
            handleFileSelect();
        });
        
        function handleFileSelect() {
            const file = fileInput.files[0];
            
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    showMessage('<i class="fas fa-exclamation-circle mr-2"></i>Arquivo muito grande! Tamanho máximo: 10MB', 'error');
                    fileInput.value = '';
                    fileInfo.classList.add('hidden');
                    return;
                }
                
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.classList.remove('hidden');
                
                // Habilitar botão de submit
                submitBtn.disabled = false;
                
                // Focar no botão de submit para facilitar
                setTimeout(() => {
                    submitBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            } else {
                // Se não há arquivo, esconder info
                fileInfo.classList.add('hidden');
                submitBtn.disabled = true;
            }
        }
        
        // Inicializar estado do botão
        submitBtn.disabled = true;
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const file = fileInput.files[0];
            if (!file) {
                showMessage('<i class="fas fa-exclamation-circle mr-2"></i>Por favor, selecione um arquivo', 'error');
                fileInfo.classList.add('hidden');
                return;
            }
            
            // Prevenir múltiplos envios
            if (submitBtn.disabled) {
                return;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            
            submitBtn.disabled = true;
            progressContainer.classList.remove('hidden');
            statusMessage.classList.add('hidden');
            
            // Não limpar o arquivo ainda - manter visível durante processamento
            
            try {
                const response = await fetch('upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('<i class="fas fa-check-circle mr-2"></i>Arquivo enviado com sucesso! Processando...', 'success');
                    const jobId = result.job_id;
                    pollStatus(jobId);
                } else {
                    showMessage(`<i class="fas fa-exclamation-circle mr-2"></i>${result.message || 'Erro ao enviar arquivo'}`, 'error');
                    submitBtn.disabled = false;
                    progressContainer.classList.add('hidden');
                }
            } catch (error) {
                showMessage(`<i class="fas fa-exclamation-triangle mr-2"></i>Erro ao enviar arquivo: ${error.message}`, 'error');
                submitBtn.disabled = false;
                progressContainer.classList.add('hidden');
            }
        });
        
        function pollStatus(jobId) {
            const interval = setInterval(async () => {
                try {
                    const response = await fetch(`status.php?job_id=${jobId}`);
                    const status = await response.json();
                    
                    if (status.status === 'completed') {
                        clearInterval(interval);
                        progressFill.style.width = '100%';
                        progressFill.textContent = '100%';
                        progressFill.className = 'progress-fill h-full w-full transition-all duration-300 flex items-center justify-center text-white text-xs font-bold bg-gradient-to-r from-green-500 to-green-600';
                        statusText.textContent = 'Concluído!';
                        showMessage(`<i class="fas fa-check-circle mr-2"></i>Processamento concluído! ${status.total} números processados. <a href="results.php?job_id=${jobId}" class="text-[#004C97] font-bold hover:underline ml-2"><i class="fas fa-external-link-alt mr-1"></i>Ver resultados</a>`, 'success');
                        submitBtn.disabled = false;
                        // Limpar arquivo selecionado após sucesso
                        fileInput.value = '';
                        fileInfo.classList.add('hidden');
                    } else if (status.status === 'error') {
                        clearInterval(interval);
                        progressFill.className = 'progress-fill h-full w-full transition-all duration-300 flex items-center justify-center text-white text-xs font-bold bg-gradient-to-r from-red-600 to-red-700';
                        showMessage(`<i class="fas fa-exclamation-circle mr-2"></i>Erro no processamento: ${status.message}`, 'error');
                        submitBtn.disabled = false;
                        progressContainer.classList.remove('hidden');
                    } else if (status.status === 'processing') {
                        const progress = status.progress || 0;
                        progressFill.style.width = progress + '%';
                        progressFill.textContent = progress + '%';
                        statusText.textContent = `Processando... ${status.processed || 0} de ${status.total || 0} números`;
                        
                        // Mudar cor conforme progresso usando cores TIM
                        if (progress < 25) {
                            progressFill.className = 'progress-fill h-full w-0 transition-all duration-300 flex items-center justify-center text-white text-xs font-bold bg-[#E30613]'; // Vermelho TIM
                        } else if (progress < 50) {
                            progressFill.className = 'progress-fill h-full w-0 transition-all duration-300 flex items-center justify-center text-white text-xs font-bold bg-[#FFD100]'; // Amarelo TIM
                        } else if (progress < 75) {
                            progressFill.className = 'progress-fill h-full w-0 transition-all duration-300 flex items-center justify-center text-white text-xs font-bold bg-[#004C97]'; // Azul TIM
                        } else {
                            progressFill.className = 'progress-fill h-full w-0 transition-all duration-300 flex items-center justify-center text-white text-xs font-bold bg-green-600'; // Verde para completo
                        }
                    }
                } catch (error) {
                    console.error('Erro ao verificar status:', error);
                }
            }, 2000);
        }
        
        function showMessage(message, type) {
            statusMessage.innerHTML = message;
            statusMessage.className = 'status-message mt-4 p-3 rounded-lg block shadow-sm';
            
            if (type === 'success') {
                statusMessage.classList.add('bg-green-50', 'text-green-800', 'border-l-4', 'border-green-500');
            } else if (type === 'error') {
                statusMessage.classList.add('bg-red-50', 'text-red-800', 'border-l-4', 'border-red-500');
            } else {
                statusMessage.classList.add('bg-blue-50', 'text-blue-800', 'border-l-4', 'border-blue-500');
            }
        }
    </script>
</body>
</html>
