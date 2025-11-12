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

    // Fallback: delegated handlers so different selectors still work
    document.addEventListener('click', (event) => {
        const openBtn = event.target && event.target.closest ? event.target.closest('#chatbot-toggle, .chatbot-toggle, [data-chatbot-toggle]') : null;
        if (openBtn && chatbot) {
            chatbot.style.display = 'flex';
            if (openBtn.style) openBtn.style.display = 'none';
        }

        const closeBtn = event.target && event.target.closest ? event.target.closest('#chatbot-close, .chatbot-close, [data-chatbot-close]') : null;
        if (closeBtn && chatbot) {
            chatbot.style.display = 'none';
            const toggleEl = document.querySelector('#chatbot-toggle, .chatbot-toggle, [data-chatbot-toggle]');
            if (toggleEl && toggleEl.style) toggleEl.style.display = 'inline-flex';
        }
    });

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

    const chatbotBody = document.querySelector('.chatbot-body');
    if (chatbotBody) {
        const appendMessage = (sender, message) => {
            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble';
            bubble.innerHTML = `<strong>${sender}:</strong> ${message}`;
            chatbotBody.appendChild(bubble);
            chatbotBody.scrollTop = chatbotBody.scrollHeight;
        };

        const renderOptions = (buttons, stepAttr = 'root') => {
            const container = document.createElement('div');
            container.className = 'quick-replies';
            container.setAttribute('data-step', stepAttr);
            buttons.forEach(({ label, value }) => {
                const btn = document.createElement('button');
                btn.className = 'qr';
                btn.setAttribute('data-option', value);
                btn.textContent = label;
                container.appendChild(btn);
            });
            chatbotBody.appendChild(container);
            chatbotBody.scrollTop = chatbotBody.scrollHeight;
        };

        const restart = () => {
            renderOptions([
                { label: 'Book a repair', value: 'repair' },
                { label: 'Find accessories', value: 'accessories' },
                { label: 'Store hours', value: 'hours' }
            ], 'root');
        };

        const bootChat = () => {
            if (chatbotBody.getAttribute('data-booted') === 'true') return;
            appendMessage('Assistant', 'Hi! How can I help today?');
            restart();
            chatbotBody.setAttribute('data-booted', 'true');
        };

        const handleRoot = (value) => {
            switch (value) {
                case 'repair': {
                    appendMessage('Assistant', 'What device needs repair?');
                    renderOptions([
                        { label: 'iPhone', value: 'device_iphone' },
                        { label: 'Android phone', value: 'device_android' },
                        { label: 'Tablet', value: 'device_tablet' }
                    ], 'repair_device');
                    break;
                }
                case 'accessories': {
                    appendMessage('Assistant', 'Great! Which accessory are you looking for?');
                    renderOptions([
                        { label: 'Screen protectors', value: 'acc_screen' },
                        { label: 'Cases', value: 'acc_cases' },
                        { label: 'Chargers', value: 'acc_chargers' },
                        { label: 'Cables', value: 'acc_cables' },
                        { label: 'Audio', value: 'acc_audio' }
                    ], 'accessories_cat');
                    break;
                }
                case 'hours': {
                    appendMessage('Assistant', 'We’re open Mon–Sat: 9:00 AM – 7:00 PM, Sun: 10:00 AM – 5:00 PM.');
                    appendMessage('Assistant', 'What else can I help with?');
                    restart();
                    break;
                }
                default:
                    break;
            }
        };

        const handleRepairDevice = (value) => {
            const device = value.replace('device_', '');
            const deviceLabel = device === 'iphone' ? 'iPhone' : device === 'android' ? 'Android phone' : 'Tablet';
            appendMessage('Assistant', `Got it — ${deviceLabel}. What seems to be the issue?`);
            renderOptions([
                { label: 'Screen damage', value: `issue_screen_${device}` },
                { label: 'Battery problem', value: `issue_battery_${device}` },
                { label: 'Not charging', value: `issue_charging_${device}` },
                { label: 'Other issue', value: `issue_other_${device}` }
            ], 'repair_issue');
        };

        const handleRepairIssue = (value) => {
            const parts = value.split('_'); // ["issue", "<type>", "<device>"]
            const issueType = parts[1];
            const device = parts[2];
            const issueLabel = issueType === 'screen' ? 'Screen damage' : issueType === 'battery' ? 'Battery problem' : issueType === 'charging' ? 'Charging issue' : 'Other issue';
            const deviceLabel = device === 'iphone' ? 'iPhone' : device === 'android' ? 'Android phone' : 'Tablet';

            appendMessage('Assistant', `Thanks! I recommend booking now so a technician can help.`);
            const query = encodeURI(`?device=${deviceLabel}&issue=${issueLabel}`);
            const container = document.createElement('div');
            container.className = 'quick-replies';
            container.innerHTML = `<a class="qr link" href="booking.php${query}">Go to Booking</a>`;
            chatbotBody.appendChild(container);

            appendMessage('Assistant', 'Need anything else?');
            restart();
        };

        const handleAccessoriesCat = (value) => {
            const map = {
                acc_screen: 'Screen Protector',
                acc_cases: 'Case',
                acc_chargers: 'Charger',
                acc_cables: 'Cable',
                acc_audio: 'Audio'
            };
            const label = map[value] || 'Accessory';
            appendMessage('Assistant', `Here are ${label.toLowerCase()} options.`);
            const q = encodeURIComponent(label);
            const container = document.createElement('div');
            container.className = 'quick-replies';
            container.innerHTML = `<a class="qr link" href="shop.php?search=${q}">Open Shop</a>`;
            chatbotBody.appendChild(container);

            appendMessage('Assistant', 'Need anything else?');
            restart();
        };

        chatbotBody.addEventListener('click', (e) => {
            const target = e.target;
            const el = target && target.closest ? target.closest('.qr') : null;
            if (!el) return;

            const option = el.getAttribute('data-option');
            const container = el.closest('.quick-replies');
            const step = container ? container.getAttribute('data-step') : 'root';

            // If this is a link without a routing option, let it navigate
            if (!option) return;

            // Mark user selection visually
            const userBubble = document.createElement('div');
            userBubble.className = 'chat-bubble';
            userBubble.innerHTML = `<strong>You:</strong> ${el.textContent}`;
            chatbotBody.appendChild(userBubble);

            // Disable current options
            if (container) {
                const btns = container.querySelectorAll('button.qr');
                btns.forEach((b) => b.setAttribute('disabled', 'true'));
            }

            // Route by step
            switch (step) {
                case 'root':
                    handleRoot(option);
                    break;
                case 'repair_device':
                    handleRepairDevice(option);
                    break;
                case 'repair_issue':
                    handleRepairIssue(option);
                    break;
                case 'accessories_cat':
                    handleAccessoriesCat(option);
                    break;
                default:
                    break;
            }
        });

        // Seed initial greeting and options once
        bootChat();
    }
});


