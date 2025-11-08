document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('nav');
    if (navToggle && nav) {
        navToggle.addEventListener('click', () => {
            nav.classList.toggle('open');
        });
        document.addEventListener('click', (event) => {
            if (!nav.contains(event.target) && !navToggle.contains(event.target)) {
                nav.classList.remove('open');
            }
        });
    }

    const chatbot = document.getElementById('chatbot');
    const chatbotToggle = document.getElementById('chatbot-toggle');
    const chatbotClose = document.getElementById('chatbot-close');

    if (chatbot && chatbotToggle) {
        chatbotToggle.addEventListener('click', () => {
            chatbot.style.display = 'flex';
            chatbotToggle.style.display = 'none';
        });
    }

    if (chatbot && chatbotClose) {
        chatbotClose.addEventListener('click', () => {
            chatbot.style.display = 'none';
            chatbotToggle.style.display = 'inline-flex';
        });
    }

    const fadeElements = document.querySelectorAll('.hero, .features article, .product-card, .service-card, .card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.2
    });

    fadeElements.forEach((el) => {
        el.classList.add('fade-in');
        observer.observe(el);
    });

    const chatbotInput = document.querySelector('#chatbot input[type="text"]');
    const chatbotBody = document.querySelector('.chatbot-body');
    const chatbotSend = document.querySelector('.chatbot-input button');
    if (chatbotInput && chatbotSend && chatbotBody) {
        const suggestions = {
            repair: 'You can book a repair by visiting the Book Repair page and completing the form. We will confirm within minutes.',
            screen: 'Our screen protectors are crafted for clarity and strength. Check the Shop page for details.',
            battery: 'Battery running low fast? Select Battery Replacement under Services or book a repair.',
            hours: 'We are open Monday to Saturday 9 AM - 7 PM, Sunday 10 AM - 5 PM.'
        };

        const handleChat = () => {
            const text = chatbotInput.value.trim();
            if (!text) {
                return;
            }
            appendMessage('You', text);
            const response = getResponse(text.toLowerCase());
            appendMessage('Assistant', response);
            chatbotInput.value = '';
        };

        const appendMessage = (sender, message) => {
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble';
            bubble.innerHTML = `<strong>${sender}:</strong> ${message}`;
            chatbotBody.appendChild(bubble);
            chatbotBody.scrollTop = chatbotBody.scrollHeight;
        };

        const getResponse = (query) => {
            for (const key of Object.keys(suggestions)) {
                if (query.includes(key)) {
                    return suggestions[key];
                }
            }
            return 'Thanks for reaching out! You can book repairs via the Book Repair page or explore accessories in the Shop. Need more help? Call us at +1 (555) 987-6543.';
        };

        chatbotSend.addEventListener('click', handleChat);
        chatbotInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleChat();
            }
        });
    }
});


