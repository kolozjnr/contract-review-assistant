<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta viewport="width=device-width, initial-scale=1.0">
    <title>Contract Review Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>
<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen"> 
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Contract Review Assistant</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Upload your contract document or paste the text directly for AI-powered analysis and insights. let <b>Jon Von Neuman do his work</b> </p>
        </div>

        <!-- Main Content -->
        <div x-data="contractReview()" class="space-y-8">
            <!-- Tab Navigation -->
            <div class="flex justify-center mb-8">
                <div class="bg-white rounded-lg p-1 shadow-md">
                    <button 
                        @click="activeTab = 'text'"
                        :class="activeTab === 'text' ? 'bg-blue-500 text-white' : 'text-gray-600 hover:text-blue-500'"
                        class="px-6 py-3 rounded-md font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Paste Text
                    </button>
                    <button 
                        @click="activeTab = 'upload'"
                        :class="activeTab === 'upload' ? 'bg-blue-500 text-white' : 'text-gray-600 hover:text-blue-500'"
                        class="px-6 py-3 rounded-md font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Upload File
                    </button>
                </div>
            </div>

            <!-- Text Input Form -->
            <div x-show="activeTab === 'text'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="bg-white rounded-xl shadow-lg p-8">
                <div class="flex items-center mb-6">
                    <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h2 class="text-2xl font-semibold text-gray-900">Paste Contract Text</h2>
                </div>
                
                <form @submit.prevent="submitText()" class="space-y-6">
                    <div>
                        <label for="contractText" class="block text-sm font-medium text-gray-700 mb-2">Contract Content</label>
                        <textarea 
                            id="contractText"
                            x-model="contractText"
                            placeholder="Paste your contract text here..."
                            rows="12"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors duration-200"
                            required
                        ></textarea>
                        <p class="mt-2 text-sm text-gray-500">Paste the full contract text for comprehensive analysis.</p>
                    </div>
                    
                    <div>
                        <label for="contractName" class="block text-sm font-medium text-gray-700 mb-2">Contract Name (Optional)</label>
                        <input 
                            type="text" 
                            id="contractName"
                            x-model="contractName"
                            placeholder="e.g., Service Agreement 2024"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                        >
                    </div>
                    
                    <button 
                        type="submit"
                        :disabled="!contractText.trim() || isProcessing"
                        :class="!contractText.trim() || isProcessing ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600'"
                        class="w-full py-4 px-6 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <span x-show="!isProcessing">Analyze Contract</span>
                        <span x-show="isProcessing" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </form>
            </div>

            <!-- File Upload Form -->
            <div x-show="activeTab === 'upload'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="bg-white rounded-xl shadow-lg p-8">
                <div class="flex items-center mb-6">
                    <svg class="w-8 h-8 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <h2 class="text-2xl font-semibold text-gray-900">Upload Contract File</h2>
                </div>
                
                <form @submit.prevent="submitFile()" class="space-y-6" enctype="multipart/form-data">
                    <!-- Drag and Drop Area -->
                    <div 
                        @drop.prevent="handleDrop($event)"
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        :class="dragOver ? 'border-blue-400 bg-blue-50' : 'border-gray-300'"
                        class="relative border-2 border-dashed rounded-lg p-8 text-center transition-colors duration-200"
                    >
                        <div x-show="!selectedFile">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-lg text-gray-600 mb-2">Drop your contract file here, or</p>
                            <label for="fileInput" class="cursor-pointer">
                                <span class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors duration-200">
                                    Browse Files
                                </span>
                                <input 
                                    type="file" 
                                    id="fileInput"
                                    @change="handleFileSelect($event)"
                                    accept=".pdf,.doc,.docx"
                                    class="hidden"
                                >
                            </label>
                            <p class="text-sm text-gray-500 mt-2">Supports PDF, DOC, and DOCX files (max 10MB)</p>
                        </div>
                        
                        <!-- Selected File Display -->
                        <div x-show="selectedFile" class="flex items-center justify-center space-x-4">
                            <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-left">
                                <p class="text-lg font-medium text-gray-900" x-text="selectedFile?.name"></p>
                                <p class="text-sm text-gray-500" x-text="formatFileSize(selectedFile?.size)"></p>
                            </div>
                            <button 
                                type="button"
                                @click="clearFile()"
                                class="text-red-500 hover:text-red-700 transition-colors duration-200"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <button 
                        type="submit"
                        :disabled="!selectedFile || isProcessing"
                        :class="!selectedFile || isProcessing ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-500 hover:bg-green-600'"
                        class="w-full py-4 px-6 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                        <span x-show="!isProcessing">Upload & Analyze</span>
                        <span x-show="isProcessing" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Analyzing...
                        </span>
                    </button>
                </form>
            </div>

            <!-- Results Section -->
                <div x-show="showResults" x-cloak x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 transform translate-y-8" x-transition:enter-end="opacity-100 transform translate-y-0" class="bg-white rounded-xl shadow-lg p-8">
                <h3 class="text-2xl font-semibold text-gray-900 mb-6">Analysis Results</h3>
                <div class="bg-gray-50 rounded-lg p-6">
                    <p class="text-gray-600" x-text="result" x-cloak></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function contractReview() {
            return {
                activeTab: 'text',
                contractText: '',
                contractName: '',
                selectedFile: null,
                dragOver: false,
                isProcessing: false,
                showResults: false,
                result: '',

               async submitText() {
                    this.isProcessing = true;
                    this.showResults = false;
                    this.result = '';

                    try {
                        const response = await fetch('/review-contract', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                contractText: this.contractText,
                                contractName: this.contractName
                            })
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();
                        console.log(data);
                        if (data.success && data.analysis && 
                            data.analysis.candidates && data.analysis.candidates.length > 0 &&
                            data.analysis.candidates[0].content &&
                            data.analysis.candidates[0].content.parts && 
                            data.analysis.candidates[0].content.parts.length > 0 &&
                            data.analysis.candidates[0].content.parts[0].text) {
                                
                            this.result = data.analysis.candidates[0].content.parts[0].text;
                        } else {
                            this.result = '⚠️ No review received from AI. Please try again.';
                        }

                    } catch (error) {
                        console.error('Error submitting contract for review:', error);
                        //this.result = '❌ Error submitting contract for review. Check the console for details.';
                        this.result = '😔 This is definitely my fault, you may want to try again as i am working to fix it.';
                    } finally {
                        this.isProcessing = false;
                        this.showResults = true;
                    }
                },

                async submitFile() {
                this.isProcessing = true;
                this.showResults = false;
                this.result = '';

                if (!this.selectedFile) {
                    alert('Please select a file to upload.');
                    this.isProcessing = false; // Reset state if no file
                    return;
                }

                const formData = new FormData();
                formData.append('contractFile', this.selectedFile);

                try {
                    const response = await fetch('/review-contract-file', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    console.log(data);

                    if (data.success && data.analysis && 
                        data.analysis.candidates && data.analysis.candidates.length > 0 &&
                        data.analysis.candidates[0].content &&
                        data.analysis.candidates[0].content.parts && 
                        data.analysis.candidates[0].content.parts.length > 0 &&
                        data.analysis.candidates[0].content.parts[0].text) {
                            
                        this.result = data.analysis.candidates[0].content.parts[0].text;
                    } else {
                        this.result = '⚠️ No review received from AI. Please try again.';
                        console.error('API response structure is missing the expected text content:', data);
                    }
                } catch (error) {
                    console.error('Error submitting contract file for review:', error);
                    //this.result = '❌ Error submitting contract for review. Check the console for details.';
                    this.result = ' 💀 aghhhh sorry this is happening to you Jon Von neuman is working on it 🫡';
                } finally { // The crucial 'finally' block
                    this.isProcessing = false;
                    this.showResults = true;
                }
            },

                handleFileSelect(event) {
                    const file = event.target.files[0];
                    this.validateAndSetFile(file);
                },

                handleDrop(event) {
                    this.dragOver = false;
                    const file = event.dataTransfer.files[0];
                    this.validateAndSetFile(file);
                },

                validateAndSetFile(file) {
                    if (!file) return;
                    
                    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    const maxSize = 10 * 1024 * 1024; // 10MB
                    
                    if (!validTypes.includes(file.type)) {
                        alert('Please select a PDF, DOC, or DOCX file.');
                        return;
                    }
                    
                    if (file.size > maxSize) {
                        alert('File size must be less than 10MB.');
                        return;
                    }
                    
                    this.selectedFile = file;
                },

                clearFile() {
                    this.selectedFile = null;
                    document.getElementById('fileInput').value = '';
                },

                formatFileSize(bytes) {
                    if (!bytes) return '';
                    const sizes = ['Bytes', 'KB', 'MB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(1024));
                    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
                },

                  formatAnalysisText(text) {
                    if (!text) return 'Analysis results will appear here...';
                    
                    // Convert markdown-style formatting to HTML
                    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
                    text = text.replace(/\n\n/g, '</p><p>');
                    text = text.replace(/\n/g, '<br>');
                    text = '<p>' + text + '</p>';
                    
                    return text;
                }
            };
        }
    </script>
</body>
</html>