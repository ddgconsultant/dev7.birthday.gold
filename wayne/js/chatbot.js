// Wayne Chatbot - AI Integration Script

class Chatbot {
    constructor() {
        this.messages = [];
        this.isTyping = false;
        
        // Configuration - to be updated with your API keys
        this.config = {
            apiEndpoint: 'api/chat.php',
            apiKey: '', // Your API key will go here
            model: '', // Model name (e.g., 'gpt-3.5-turbo', 'claude-3-opus', etc.)
            provider: '', // 'openai', 'anthropic', 'google', etc.
        };
        
        this.init();
    }
    
    init() {
        // Get DOM elements
        this.chatForm = document.getElementById('chatForm');
        this.chatInput = document.getElementById('chatInput');
        this.chatMessages = document.getElementById('chatMessages');
        this.typingIndicator = document.getElementById('typingIndicator');
        this.clearButton = document.getElementById('clearChat');
        this.sendButton = document.getElementById('sendButton');
        
        // Bind events
        if (this.chatForm) {
            this.chatForm.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        
        if (this.clearButton) {
            this.clearButton.addEventListener('click', () => this.clearChat());
        }
        
        // Load saved messages if any
        this.loadMessages();
        
        // Focus input
        if (this.chatInput) {
            this.chatInput.focus();
        }
    }
    
    handleSubmit(e) {
        e.preventDefault();
        
        const message = this.chatInput.value.trim();
        if (!message) return;
        
        // Add user message
        this.addMessage(message, 'user');
        
        // Clear input
        this.chatInput.value = '';
        
        // Send to AI
        this.sendToAI(message);
    }
    
    addMessage(text, sender = 'bot') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const avatar = document.createElement('div');
        avatar.className = 'message-avatar';
        avatar.textContent = sender === 'user' ? '👤' : '🤖';
        
        const content = document.createElement('div');
        content.className = 'message-content';
        
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        bubble.textContent = text;
        
        content.appendChild(bubble);
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(content);
        
        this.chatMessages.appendChild(messageDiv);
        
        // Scroll to bottom
        this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        
        // Save message
        this.messages.push({ text, sender, timestamp: new Date() });
        this.saveMessages();
    }
    
    async sendToAI(message) {
        // Show typing indicator
        this.showTyping();
        
        try {
            // Check if API is configured
            if (!this.config.apiKey) {
                this.hideTyping();
                this.addMessage('Please configure your API key first. Add your API credentials in the code or use the configuration panel.', 'bot');
                return;
            }
            
            // Prepare the request - filter out greeting and only send relevant history
            const filteredHistory = this.messages.filter(msg => {
                return msg.text !== "Hello! I'm your AI assistant. How can I help you today?" &&
                       msg.text !== "Hello! I'm powered by Claude Opus 4. I'm here to help with any questions or tasks you have. What would you like to discuss today?";
            });
            
            const requestData = {
                message: message,
                apiKey: this.config.apiKey,
                model: this.config.model,
                provider: this.config.provider,
                history: filteredHistory.slice(-6) // Send last 6 messages (3 exchanges) for context
            };
            
            // Send to backend
            const response = await fetch(this.config.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Hide typing indicator
            this.hideTyping();
            
            // Add AI response
            if (data.success && data.response) {
                this.addMessage(data.response, 'bot');
            } else {
                this.addMessage(data.error || 'Sorry, I encountered an error. Please try again.', 'bot');
            }
            
        } catch (error) {
            console.error('Error sending to AI:', error);
            this.hideTyping();
            
            // Fallback responses for demo mode
            if (!this.config.apiKey) {
                const demoResponses = [
                    "This is a demo response. Please add your API key to enable real AI responses.",
                    "I'm currently in demo mode. Configure your API credentials to unlock my full potential!",
                    "To get started with real AI responses, you'll need to add your API key in the configuration.",
                    "Hello! I'm ready to chat once you configure your AI provider credentials."
                ];
                const randomResponse = demoResponses[Math.floor(Math.random() * demoResponses.length)];
                this.addMessage(randomResponse, 'bot');
            } else {
                this.addMessage('Sorry, I encountered an error connecting to the AI service. Please check your configuration and try again.', 'bot');
            }
        }
    }
    
    showTyping() {
        this.isTyping = true;
        this.typingIndicator.style.display = 'inline-flex';
        this.sendButton.disabled = true;
    }
    
    hideTyping() {
        this.isTyping = false;
        this.typingIndicator.style.display = 'none';
        this.sendButton.disabled = false;
    }
    
    clearChat() {
        if (confirm('Are you sure you want to clear the chat history?')) {
            // Keep only the welcome message
            const welcomeMessage = this.chatMessages.querySelector('.message');
            this.chatMessages.innerHTML = '';
            if (welcomeMessage) {
                this.chatMessages.appendChild(welcomeMessage);
            }
            
            // Clear saved messages
            this.messages = [];
            this.saveMessages();
            
            // Focus input
            this.chatInput.focus();
        }
    }
    
    saveMessages() {
        // Save to localStorage for persistence
        try {
            localStorage.setItem('chatbot_messages', JSON.stringify(this.messages));
        } catch (e) {
            console.error('Failed to save messages:', e);
        }
    }
    
    loadMessages() {
        // Load from localStorage
        try {
            const saved = localStorage.getItem('chatbot_messages');
            if (saved) {
                this.messages = JSON.parse(saved);
                // Don't reload visual messages on page refresh to keep it clean
                // Uncomment below to restore full chat history on reload
                // this.messages.forEach(msg => {
                //     if (msg.sender !== 'bot' || msg.text !== 'Hello! I\'m your AI assistant. How can I help you today?') {
                //         this.addMessage(msg.text, msg.sender);
                //     }
                // });
            }
        } catch (e) {
            console.error('Failed to load messages:', e);
        }
    }
    
    // Method to update configuration
    configure(apiKey, provider = 'openai', model = 'gpt-3.5-turbo') {
        this.config.apiKey = apiKey;
        this.config.provider = provider;
        this.config.model = model;
        
        console.log('Chatbot configured with:', {
            provider: provider,
            model: model,
            keyLength: apiKey ? apiKey.length : 0
        });
    }
}

// Initialize chatbot when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.chatbot = new Chatbot();
    
    // Configure with Anthropic Claude API
    window.chatbot.configure(
        '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',
        'anthropic',
        'claude-opus-4-20250514'  // Using Claude Opus 4 - most powerful model
    );
});

// Export for external configuration
window.configureChatbot = function(apiKey, provider, model) {
    if (window.chatbot) {
        window.chatbot.configure(apiKey, provider, model);
    }
};