self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/notifications', self.location.origin);
    if (target.origin !== self.location.origin) return;
    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async (windows) => {
        const existing = windows.find((window) => window.url === target.href);
        if (existing) return existing.focus();
        return clients.openWindow(target.href);
    }));
});

importScripts('https://www.gstatic.com/firebasejs/12.3.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.3.0/firebase-messaging-compat.js');
firebase.initializeApp(@json($firebaseConfig));
firebase.messaging().onBackgroundMessage((payload) => {
    const data = payload.data || {};
    return self.registration.showNotification(data.title || 'ACEMIX', {
        body: data.body || '',
        tag: data.notification_id,
        data: { url: data.url || '/notifications' },
    });
});
