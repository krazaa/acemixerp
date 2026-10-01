(async () => {
    const script = document.querySelector('[data-push-config]');
    if (!script) return;
    const toggle = document.getElementById('push-toggle');
    const test = document.getElementById('push-test');
    const status = document.getElementById('push-status');
    const prompt = document.getElementById('push-device-prompt');
    const register = document.getElementById('push-register-device');
    const promptMessage = document.getElementById('push-device-message');
    const dismissedKey = `acemix-push-dismissed:${script.dataset.pushUser}:${script.dataset.pushLogin}`;
    const disabledKey = `acemix-push-disabled:${script.dataset.pushUser}`;
    const disabledPreference = localStorage.getItem(disabledKey);
    const pushDisabled = disabledPreference === '1'
        || (disabledPreference === null && localStorage.getItem('acemix-push-disabled') === '1');
    const modal = prompt && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(prompt) : null;
    const showPrompt = () => {
        if (sessionStorage.getItem(dismissedKey) !== '1') modal?.show();
    };
    prompt?.addEventListener('hide.bs.modal', () => sessionStorage.setItem(dismissedKey, '1'));
    let messaging;
    let sdk;
    let registration;
    let busy = false;
    const setStatus = (message) => {
        if (status) status.textContent = message;
        if (promptMessage) promptMessage.textContent = message;
    };
    const setEnabled = (enabled) => {
        if (toggle) { toggle.checked = enabled; toggle.disabled = false; }
        if (test) test.disabled = !enabled;
    };
    const request = async (url, method = 'GET', body) => {
        const response = await fetch(url, {
            method, credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            ...(body ? { body: JSON.stringify(body) } : {}),
        });
        if (!response.ok) throw new Error(`Request failed (${response.status}).`);
        return response.json();
    };
    if (!window.isSecureContext || !('serviceWorker' in navigator) || !('Notification' in window) || !('PushManager' in window)) {
        setStatus('Browser push unavailable. HTTPS or localhost is required.');
        showPrompt();
        return;
    }
    try {
        const config = await request(script.dataset.pushConfig);
        if (!config.enabled) { setStatus('Browser push is not configured.'); return; }
        const initialise = async () => {
            if (messaging) return;
            const app = await import('https://www.gstatic.com/firebasejs/12.3.0/firebase-app.js');
            sdk = await import('https://www.gstatic.com/firebasejs/12.3.0/firebase-messaging.js');
            if (!(await sdk.isSupported())) throw new Error('This browser does not support Firebase push.');
            registration = await navigator.serviceWorker.register(config.workerUrl, { scope: '/' });
            await navigator.serviceWorker.ready;
            messaging = sdk.getMessaging(app.initializeApp(config.firebase, 'acemix-push'));
            sdk.onMessage(messaging, (payload) => {
                const data = payload.data || {};
                registration.showNotification(data.title || 'ACEMIX', {
                    body: data.body || '', tag: data.notification_id,
                    data: { url: data.url || '/notifications' },
                }).catch(() => setStatus('A new notification is available.'));
            });
        };
        const subscribe = async () => {
            await initialise();
            const token = await sdk.getToken(messaging, { vapidKey: config.vapidKey, serviceWorkerRegistration: registration });
            if (!token) throw new Error('Firebase did not return a browser token.');
            await request(script.dataset.pushStore, 'POST', { token });
            localStorage.setItem(disabledKey, '0');
            setEnabled(true);
            setStatus('Browser push enabled');
            modal?.hide();
        };
        const enable = async () => {
            if (busy) return;
            busy = true;
            if (register) register.disabled = true;
            if (toggle) toggle.disabled = true;
            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    throw new Error(permission === 'denied'
                        ? 'Notifications are blocked. Allow notifications in this site\'s browser settings, then retry.'
                        : 'Notification permission was not granted.');
                }
                setStatus('Registering device...');
                await subscribe();
            } catch (error) {
                setEnabled(false);
                setStatus(error.message || 'Unable to register this device.');
            } finally {
                busy = false;
                if (register) register.disabled = false;
                if (toggle) toggle.disabled = false;
            }
        };
        register?.addEventListener('click', enable);
        if (register) register.disabled = false;
        setEnabled(false);
        if (Notification.permission === 'denied') {
            setStatus('Notifications are blocked. Allow notifications in this site\'s browser settings, then retry.');
        } else {
            if (status) status.textContent = 'Browser push disabled';
        }
        toggle?.addEventListener('change', async () => {
            if (busy) return;
            if (toggle.checked) {
                await enable();
                return;
            }
            busy = true;
            toggle.disabled = true;
            try {
                await request(script.dataset.pushStore, 'DELETE');
                localStorage.setItem(disabledKey, '1');
                setEnabled(false);
                setStatus('Browser push disabled');
                if (messaging) await sdk.deleteToken(messaging);
            } catch (error) {
                setEnabled(false);
                setStatus(error.message || 'Unable to connect browser push.');
            } finally { busy = false; toggle.disabled = false; }
        });
        test?.addEventListener('click', async () => {
            test.disabled = true;
            try {
                const result = await request(script.dataset.pushTest, 'POST');
                setStatus(result.message);
            } catch (error) { setStatus(error.message); }
            finally { test.disabled = false; }
        });
        if (Notification.permission === 'granted' && !pushDisabled) {
            busy = true;
            if (toggle) toggle.disabled = true;
            try { await subscribe(); }
            finally { busy = false; if (toggle) toggle.disabled = false; }
        } else if (disabledPreference !== '1') {
            showPrompt();
        }
    } catch (error) {
        setEnabled(false);
        setStatus(error.message || 'Unable to connect browser push.');
        showPrompt();
    }
})();
