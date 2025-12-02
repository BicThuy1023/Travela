/**
 * Chatbot Widget JavaScript
 * Xử lý tương tác với chatbot AI
 */

(function() {
    'use strict';

    // DOM Elements - sẽ được khởi tạo trong initChatbot()
    let toggleBtn = null;
    let closeIcon = null;
    let chatIcon = null;
    let chatWindow = null;
    let messagesContainer = null;
    let chatbotForm = null;
    let chatbotInput = null;
    let chatbotSend = null;
    let chatbotClear = null;
    let suggestionsContainer = null;
    let suggestionButtons = [];

    // State
    let messages = [];
    let isLoading = false;
    let conversationContext = [];
    let isInitialized = false;

    /**
     * Khởi tạo chatbot - chạy khi DOM đã sẵn sàng
     */
    function initChatbot() {
        // Tránh khởi tạo nhiều lần
        if (isInitialized) {
            console.warn('Chatbot đã được khởi tạo rồi');
            return;
        }

        // Lấy tất cả DOM elements cần thiết
        toggleBtn = document.getElementById('chatbot-toggle');
        closeIcon = document.getElementById('chatbot-close-icon');
        chatIcon = document.getElementById('chatbot-icon');
        chatWindow = document.getElementById('chatbot-window');
        messagesContainer = document.getElementById('chatbot-messages');
        chatbotForm = document.getElementById('chatbot-form');
        chatbotInput = document.getElementById('chatbot-input');
        chatbotSend = document.getElementById('chatbot-send');
        chatbotClear = document.getElementById('chatbot-clear');
        suggestionsContainer = document.getElementById('chatbot-suggestions');

        // Kiểm tra các element quan trọng
        if (!toggleBtn) {
            console.error('❌ Không tìm thấy nút chatbot-toggle');
            return;
        }
        if (!chatWindow) {
            console.error('❌ Không tìm thấy chatbot-window');
            return;
        }
        if (!messagesContainer) {
            console.error('❌ Không tìm thấy chatbot-messages');
            return;
        }
        if (!chatbotForm) {
            console.error('❌ Không tìm thấy chatbot-form');
            return;
        }

        console.log('✅ Chatbot elements đã được tìm thấy');

        // Lấy suggestion buttons
        suggestionButtons = document.querySelectorAll('.suggestion-btn');

        // Load messages từ localStorage (KHÔNG render lại, chỉ load vào mảng)
        loadMessagesFromStorage();

        // Gắn event listeners
        toggleBtn.addEventListener('click', toggleChat);
        chatbotForm.addEventListener('submit', handleSubmit);
        if (chatbotInput) {
            chatbotInput.addEventListener('input', handleInputChange);
        }
        if (chatbotClear) {
            chatbotClear.addEventListener('click', clearConversation);
        }

        // Suggestion buttons
        suggestionButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const question = btn.getAttribute('data-question');
                if (chatbotInput && question) {
                    chatbotInput.value = question;
                    handleInputChange();
                    // Tự động submit sau 100ms để đảm bảo input đã được set
                    setTimeout(() => {
                        handleSubmit(new Event('submit'));
                    }, 100);
                }
            });
        });

        // Render messages đã load (nếu có)
        if (messages.length > 0) {
            renderSavedMessages();
        }

        isInitialized = true;
        console.log('✅ Chatbot đã được khởi tạo thành công');
    }

    /**
     * Toggle chat window (mở/đóng)
     */
    function toggleChat() {
        if (!chatWindow || !chatIcon || !closeIcon) {
            console.error('❌ Không thể toggle: thiếu elements');
            return;
        }

        const isOpen = chatWindow.style.display !== 'none' && chatWindow.style.display !== '';
        
        if (isOpen) {
            // Đóng chat
            chatWindow.style.display = 'none';
            chatIcon.style.display = 'block';
            closeIcon.style.display = 'none';
        } else {
            // Mở chat
            chatWindow.style.display = 'flex';
            chatIcon.style.display = 'none';
            closeIcon.style.display = 'block';
            
            // Focus vào input
            if (chatbotInput) {
                setTimeout(() => {
                    chatbotInput.focus();
                }, 100);
            }
            
            scrollToBottom();
        }
    }

    /**
     * Handle input change - enable/disable send button
     */
    function handleInputChange() {
        if (!chatbotInput || !chatbotSend) return;
        
        const hasText = chatbotInput.value.trim().length > 0;
        chatbotSend.disabled = !hasText || isLoading;
    }

    /**
     * Handle form submit - gửi tin nhắn
     */
    async function handleSubmit(e) {
        e.preventDefault();
        
        if (!chatbotInput) return;
        
        const message = chatbotInput.value.trim();
        if (!message || isLoading) return;

        // Add user message
        addMessage('user', message);
        chatbotInput.value = '';
        handleInputChange();
        
        // Hide suggestions after first message
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }

        // Show loading
        showLoading();

        try {
            // Build conversation context (last 10 messages)
            const context = messages
                .slice(-10)
                .filter(msg => msg.type !== 'error')
                .map(msg => ({
                    role: msg.type === 'user' ? 'user' : 'assistant',
                    content: msg.text
                }));

            // Call API
            const response = await fetch('/api/ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    message: message,
                    context: context
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.data) {
                // Hide loading
                hideLoading();

                // Add bot response
                const botMessage = {
                    type: 'bot',
                    text: data.data.response,
                    functionCalled: data.data.functionCalled || null,
                    functionResult: data.data.functionResult || null
                };
                addMessage('bot', botMessage.text, botMessage.functionCalled, botMessage.functionResult);

                // Update conversation context
                conversationContext = context;
            } else {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }
        } catch (error) {
            console.error('Chatbot error:', error);
            hideLoading();
            addMessage('bot', 'Xin lỗi, tôi gặp sự cố. Vui lòng thử lại sau. 😔', null, null);
        }
    }

    /**
     * Add message to chat (chỉ render, không load từ storage)
     */
    function addMessage(type, text, functionCalled = null, functionResult = null) {
        if (!messagesContainer) {
            console.error('❌ messagesContainer không tồn tại');
            return;
        }

        const message = {
            type: type,
            text: text,
            timestamp: new Date(),
            functionCalled: functionCalled,
            functionResult: functionResult
        };

        // Chỉ push vào mảng nếu không phải đang load từ storage
        messages.push(message);
        saveMessagesToStorage();

        // Render message
        renderMessage(message);

        scrollToBottom();
        updateClearButton();
    }

    /**
     * Render một message ra DOM
     */
    function renderMessage(message) {
        if (!messagesContainer) return;

        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${message.type}-message`;

        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';

        // Add text
        const textP = document.createElement('p');
        textP.textContent = message.text;
        contentDiv.appendChild(textP);

        // Add function result (tour cards)
        if (message.functionResult && message.functionResult.success) {
            if (message.functionCalled === 'searchTours' && message.functionResult.tours) {
                const toursContainer = document.createElement('div');
                message.functionResult.tours.forEach(tour => {
                    toursContainer.appendChild(createTourCard(tour));
                });
                contentDiv.appendChild(toursContainer);
            } else if (message.functionCalled === 'getTourDetails' && message.functionResult.tour) {
                contentDiv.appendChild(createTourDetailCard(message.functionResult.tour));
            } else if (message.functionCalled === 'createBookingLink' && message.functionResult.bookingLink) {
                const bookingDiv = document.createElement('div');
                bookingDiv.className = 'tour-card';
                bookingDiv.innerHTML = `
                    <div class="tour-card-content">
                        <p><strong>${escapeHtml(message.functionResult.tourName || 'Tour')}</strong></p>
                        <p>Giá: ${formatPrice(message.functionResult.price)} VNĐ</p>
                        <a href="${message.functionResult.bookingLink}" class="tour-card-link" target="_blank">
                            Đặt tour ngay →
                        </a>
                    </div>
                `;
                contentDiv.appendChild(bookingDiv);
            }
        }

        // Add timestamp
        const timeSpan = document.createElement('span');
        timeSpan.className = 'message-time';
        timeSpan.textContent = formatTime(message.timestamp);
        contentDiv.appendChild(timeSpan);

        messageDiv.appendChild(contentDiv);
        messagesContainer.appendChild(messageDiv);
    }

    /**
     * Render tất cả messages đã load từ storage
     */
    function renderSavedMessages() {
        if (!messagesContainer) return;

        // Xóa message mặc định nếu có messages đã lưu
        const defaultMessage = messagesContainer.querySelector('.bot-message');
        if (defaultMessage && messages.length > 0) {
            defaultMessage.remove();
        }

        // Render từng message
        messages.forEach(msg => {
            renderMessage(msg);
        });

        // Hide suggestions nếu đã có messages
        if (messages.length > 0 && suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }
    }

    /**
     * Create tour card element
     */
    function createTourCard(tour) {
        const card = document.createElement('div');
        card.className = 'tour-card';
        
        let html = '';
        
        if (tour.image) {
            html += `<img src="${tour.image}" alt="${escapeHtml(tour.name)}" class="tour-card-image" onerror="this.style.display='none'">`;
        }
        
        html += `
            <div class="tour-card-content">
                <h4 class="tour-card-title">${escapeHtml(tour.name)}</h4>
                <p class="tour-card-destination">📍 ${escapeHtml(tour.destination || '')}</p>
                <div class="tour-card-footer">
                    <div class="tour-card-rating">
                        ${tour.rating ? `⭐ ${tour.rating.toFixed(1)}` : ''}
                    </div>
                    <div class="tour-card-price">${formatPrice(tour.price || 0)} VNĐ</div>
                </div>
                <a href="${tour.link || '#'}" class="tour-card-link" target="_blank">
                    Xem chi tiết
                </a>
            </div>
        `;
        
        card.innerHTML = html;
        return card;
    }

    /**
     * Create tour detail card element
     */
    function createTourDetailCard(tour) {
        const card = document.createElement('div');
        card.className = 'tour-card';
        
        let html = '';
        
        if (tour.images && tour.images.length > 0) {
            html += `<img src="${tour.images[0]}" alt="${escapeHtml(tour.name)}" class="tour-card-image" onerror="this.style.display='none'">`;
        }
        
        const description = tour.description ? escapeHtml(tour.description.substring(0, 100)) + '...' : '';
        
        html += `
            <div class="tour-card-content">
                <h4 class="tour-card-title">${escapeHtml(tour.name)}</h4>
                <p class="tour-card-destination">📍 ${escapeHtml(tour.destination || '')}</p>
                ${tour.rating ? `<div class="tour-card-rating">⭐ ${tour.rating.toFixed(1)} (${tour.totalReviews || 0} đánh giá)</div>` : ''}
                ${description ? `<p style="font-size: 12px; color: #6c757d; margin: 8px 0;">${description}</p>` : ''}
                <div class="tour-card-footer">
                    <div class="tour-card-price">${formatPrice(tour.price || 0)} VNĐ</div>
                </div>
                <a href="${tour.link || '#'}" class="tour-card-link" target="_blank">
                    Xem chi tiết tour
                </a>
            </div>
        `;
        
        card.innerHTML = html;
        return card;
    }

    /**
     * Show loading indicator
     */
    function showLoading() {
        if (!messagesContainer) return;

        isLoading = true;
        if (chatbotSend) {
            chatbotSend.disabled = true;
        }
        
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'chatbot-message bot-message';
        loadingDiv.id = 'chatbot-loading';
        loadingDiv.innerHTML = `
            <div class="message-content">
                <div class="loading-dots">
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                </div>
            </div>
        `;
        messagesContainer.appendChild(loadingDiv);
        scrollToBottom();
    }

    /**
     * Hide loading indicator
     */
    function hideLoading() {
        isLoading = false;
        const loadingDiv = document.getElementById('chatbot-loading');
        if (loadingDiv) {
            loadingDiv.remove();
        }
        handleInputChange();
    }

    /**
     * Clear conversation
     */
    function clearConversation() {
        if (!confirm('Bạn có chắc muốn xóa toàn bộ cuộc trò chuyện?')) {
            return;
        }

        messages = [];
        conversationContext = [];
        
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
        }
        
        // Add initial message
        addMessage('bot', 'Xin chào! 👋 Tôi là trợ lý ảo của hệ thống đặt tour du lịch. Tôi có thể giúp bạn:\n\n• Tìm kiếm và đặt tour\n• Tra cứu giá tour\n• Hỗ trợ thanh toán\n• Giải đáp chính sách\n\nBạn cần hỗ trợ gì ạ? 😊');
        
        // Show suggestions
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'block';
        }
        
        updateClearButton();
        localStorage.removeItem('chatbot_messages');
    }

    /**
     * Update clear button visibility
     */
    function updateClearButton() {
        if (chatbotClear) {
            chatbotClear.style.display = messages.length > 1 ? 'block' : 'none';
        }
    }

    /**
     * Scroll to bottom of messages
     */
    function scrollToBottom() {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    /**
     * Save messages to localStorage
     */
    function saveMessagesToStorage() {
        try {
            localStorage.setItem('chatbot_messages', JSON.stringify(messages));
        } catch (e) {
            console.error('Error saving messages:', e);
        }
    }

    /**
     * Load messages from localStorage (chỉ load vào mảng, không render)
     */
    function loadMessagesFromStorage() {
        try {
            const saved = localStorage.getItem('chatbot_messages');
            if (saved) {
                const parsed = JSON.parse(saved);
                // Convert timestamp strings to Date objects
                messages = parsed.map(msg => ({
                    ...msg,
                    timestamp: new Date(msg.timestamp)
                }));
                console.log(`✅ Đã load ${messages.length} messages từ localStorage`);
            }
        } catch (e) {
            console.error('Error loading messages:', e);
            messages = [];
        }
    }

    /**
     * Format time
     */
    function formatTime(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        return date.toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Format price
     */
    function formatPrice(price) {
        if (!price) return '0';
        return new Intl.NumberFormat('vi-VN').format(price);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Get CSRF token
     */
    function getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatbot);
    } else {
        // DOM đã sẵn sàng, nhưng đợi thêm một chút để đảm bảo tất cả scripts đã load
        setTimeout(initChatbot, 100);
    }
})();
