{{-- Chatbot Widget Component --}}
<div id="chatbot-widget">
    {{-- Chat Button --}}
    <button id="chatbot-toggle" class="chatbot-btn" title="Trợ lý AI">
        <i class="fas fa-comments" id="chatbot-icon"></i>
        <i class="fas fa-times" id="chatbot-close-icon" style="display: none;"></i>
    </button>

    {{-- Chat Window --}}
    <div id="chatbot-window" class="chatbot-window" style="display: none;">
        {{-- Header --}}
        <div class="chatbot-header">
            <div class="chatbot-header-content">
                <h3>Trợ lý ảo AI 🤖</h3>
                <p>Luôn sẵn sàng hỗ trợ bạn</p>
            </div>
            <button id="chatbot-clear" class="chatbot-clear-btn" title="Xóa cuộc trò chuyện" style="display: none;">
                <i class="fas fa-trash"></i>
            </button>
        </div>

        {{-- Messages Container --}}
        <div id="chatbot-messages" class="chatbot-messages">
            {{-- Initial bot message --}}
            <div class="chatbot-message bot-message">
                <div class="message-content">
                    <p>Xin chào! 👋 Tôi là trợ lý ảo của hệ thống đặt tour du lịch. Tôi có thể giúp bạn:</p>
                    <ul>
                        <li>Tìm kiếm và đặt tour</li>
                        <li>Tra cứu giá tour</li>
                        <li>Hỗ trợ thanh toán</li>
                        <li>Giải đáp chính sách</li>
                    </ul>
                    <p>Bạn cần hỗ trợ gì ạ? 😊</p>
                    <span class="message-time"></span>
                </div>
            </div>
        </div>

        {{-- Suggested Questions --}}
        <div id="chatbot-suggestions" class="chatbot-suggestions">
            <p class="suggestions-label">Câu hỏi gợi ý:</p>
            <div class="suggestions-list">
                <button class="suggestion-btn" data-question="Tìm tour ở Đà Nẵng">Tìm tour ở Đà Nẵng</button>
                <button class="suggestion-btn" data-question="Tour có giá dưới 2 triệu">Tour có giá dưới 2 triệu</button>
                <button class="suggestion-btn" data-question="Tour 3 ngày 2 đêm">Tour 3 ngày 2 đêm</button>
                <button class="suggestion-btn" data-question="Tour miền Bắc">Tour miền Bắc</button>
            </div>
        </div>

        {{-- Input Form --}}
        <form id="chatbot-form" class="chatbot-form">
            <div class="chatbot-input-wrapper">
                <input 
                    type="text" 
                    id="chatbot-input" 
                    class="chatbot-input" 
                    placeholder="Nhập câu hỏi của bạn..."
                    autocomplete="off"
                />
                <button type="submit" id="chatbot-send" class="chatbot-send-btn" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Styles --}}
<style>
#chatbot-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.chatbot-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chatbot-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.chatbot-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 380px;
    max-width: calc(100vw - 40px);
    height: 600px;
    max-height: calc(100vh - 100px);
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chatbot-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chatbot-header-content h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.chatbot-header-content p {
    margin: 4px 0 0 0;
    font-size: 12px;
    opacity: 0.9;
}

.chatbot-clear-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.chatbot-clear-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.chatbot-message {
    display: flex;
    max-width: 80%;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chatbot-message.user-message {
    align-self: flex-end;
    justify-content: flex-end;
}

.chatbot-message.bot-message {
    align-self: flex-start;
    justify-content: flex-start;
}

.message-content {
    padding: 12px 16px;
    border-radius: 18px;
    word-wrap: break-word;
}

.user-message .message-content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.bot-message .message-content {
    background: #f1f3f5;
    color: #212529;
    border-bottom-left-radius: 4px;
}

.message-content p {
    margin: 0 0 8px 0;
    line-height: 1.5;
    white-space: pre-line;
}

.message-content ul {
    margin: 8px 0;
    padding-left: 20px;
}

.message-content li {
    margin: 4px 0;
}

.message-time {
    display: block;
    font-size: 10px;
    opacity: 0.7;
    margin-top: 4px;
}

.loading-dots {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
}

.loading-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6c757d;
    animation: bounce 1.4s infinite ease-in-out;
}

.loading-dot:nth-child(1) { animation-delay: -0.32s; }
.loading-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

.chatbot-suggestions {
    padding: 12px 16px;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

.suggestions-label {
    font-size: 11px;
    color: #6c757d;
    margin: 0 0 8px 0;
}

.suggestions-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.suggestion-btn {
    font-size: 11px;
    padding: 6px 12px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s;
    color: #495057;
}

.suggestion-btn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.chatbot-form {
    padding: 12px;
    border-top: 1px solid #e9ecef;
    background: white;
}

.chatbot-input-wrapper {
    display: flex;
    gap: 8px;
}

.chatbot-input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.chatbot-input:focus {
    border-color: #667eea;
}

.chatbot-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.chatbot-send-btn:hover:not(:disabled) {
    transform: scale(1.05);
}

.chatbot-send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Tour Cards */
.tour-card {
    margin-top: 12px;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}

.tour-card-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.tour-card-content {
    padding: 12px;
}

.tour-card-title {
    font-weight: 600;
    font-size: 14px;
    margin: 0 0 4px 0;
    color: #212529;
}

.tour-card-destination {
    font-size: 12px;
    color: #6c757d;
    margin: 0 0 8px 0;
}

.tour-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}

.tour-card-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #6c757d;
}

.tour-card-price {
    font-weight: 600;
    font-size: 14px;
    color: #667eea;
}

.tour-card-link {
    display: block;
    margin-top: 8px;
    padding: 8px;
    text-align: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 12px;
    transition: opacity 0.2s;
}

.tour-card-link:hover {
    opacity: 0.9;
}

/* Responsive */
@media (max-width: 480px) {
    .chatbot-window {
        width: calc(100vw - 20px);
        right: -10px;
    }
}
</style>

{{-- Script được load trong footer, không cần load ở đây --}}

