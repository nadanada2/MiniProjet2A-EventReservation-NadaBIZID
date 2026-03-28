function bufferToBase64Url(buffer) {
    const bytes = Array.from(new Uint8Array(buffer));
    const binary = bytes.map(b => String.fromCharCode(b)).join('');
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function base64UrlToBuffer(base64url) {
    let base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const padding = '='.repeat((4 - base64.length % 4) % 4);
    base64 += padding;
    const binary = atob(base64);
    const bytes = Uint8Array.from(binary, c => c.charCodeAt(0));
    return bytes.buffer;
}

async function registerPasskey(email) {
    if (!email) { alert('Entrez votre email d\'abord'); return; }
    try {
        const optRes = await fetch('/api/auth/register/options', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email})
        });
        const options = await optRes.json();
        if (options.error) { alert(options.error); return; }

        const credential = await navigator.credentials.create({
            publicKey: {
                ...options,
                challenge: base64UrlToBuffer(options.challenge),
                user: { ...options.user, id: base64UrlToBuffer(options.user.id) }
            }
        });

        const verifyRes = await fetch('/api/auth/register/verify', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                email,
                credential: {
                    id: credential.id,
                    rawId: bufferToBase64Url(credential.rawId),
                    response: {
                        clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                        attestationObject: bufferToBase64Url(credential.response.attestationObject)
                    },
                    type: credential.type
                }
            })
        });
        const result = await verifyRes.json();
        if (result.token) {
            localStorage.setItem('jwt_token', result.token);
            alert('Passkey créée avec succès !');
            window.location.href = '/';
        }
    } catch(e) { console.error(e); alert('Erreur Passkey : ' + e.message); }
}

async function loginWithPasskey() {
    try {
        const optRes = await fetch('/api/auth/login/options', { method: 'POST' });
        const options = await optRes.json();
        if (options.error) { alert(options.error); return; }

        const assertion = await navigator.credentials.get({
            publicKey: {
                ...options,
                challenge: base64UrlToBuffer(options.challenge),
                allowCredentials: (options.allowCredentials || []).map(c => ({
                    ...c, id: base64UrlToBuffer(c.id)
                }))
            }
        });

        const verifyRes = await fetch('/api/auth/login/verify', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                credential: {
                    id: assertion.id,
                    rawId: bufferToBase64Url(assertion.rawId),
                    response: {
                        clientDataJSON: bufferToBase64Url(assertion.response.clientDataJSON),
                        authenticatorData: bufferToBase64Url(assertion.response.authenticatorData),
                        signature: bufferToBase64Url(assertion.response.signature),
                        userHandle: assertion.response.userHandle
                            ? bufferToBase64Url(assertion.response.userHandle) : null
                    },
                    type: assertion.type
                }
            })
        });
        const result = await verifyRes.json();
        if (result.token) {
            localStorage.setItem('jwt_token', result.token);
            window.location.href = '/';
        }
    } catch(e) { console.error(e); alert('Erreur Passkey : ' + e.message); }
}

function authFetch(url, options = {}) {
    const token = localStorage.getItem('jwt_token');
    return fetch(url, {
        ...options,
        headers: { ...(options.headers || {}), 'Authorization': token ? `Bearer ${token}` : '' }
    });
}