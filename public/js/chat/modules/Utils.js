// Helper functions for text and UI operations
export function getInitials(name) {
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .substring(0, 2)
        .toUpperCase();
}

export function getColorFromName(name) {
    const hue = Math.abs(
        name.split("").reduce((acc, char) => acc + char.charCodeAt(0), 0) % 360
    );
    return `hsl(${hue}, 70%, 65%)`;
}

// Format message time for display in conversation list
export function formatMessageTime(timestamp) {
    if (!timestamp) return "";

    const date = new Date(timestamp);
    const now = new Date();

    // If it's today, show time
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });
    }

    // If it's yesterday, show "Yesterday"
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return "Yesterday";
    }

    // If it's this year, show month and day
    if (date.getFullYear() === now.getFullYear()) {
        return date.toLocaleDateString([], {
            month: "short",
            day: "numeric",
        });
    }

    // Otherwise show date
    return date.toLocaleDateString([], {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

// Format date for the date divider in chat
export function formatDateForDivider(date) {
    const now = new Date();

    // If it's today
    if (date.toDateString() === now.toDateString()) {
        return "Today";
    }

    // If it's yesterday
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return "Yesterday";
    }

    // If it's within the last 7 days
    const oneWeekAgo = new Date(now);
    oneWeekAgo.setDate(now.getDate() - 7);
    if (date > oneWeekAgo) {
        return date.toLocaleDateString([], {
            weekday: "long",
        });
    }

    // Otherwise show full date
    return date.toLocaleDateString([], {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
}

// Format message text for display in chat
export function formatMessageText(text) {
    if (!text) return "";

    // Escape HTML to prevent XSS
    text = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");

    // Censor contact information in displayed messages for extra security
    text = censorContactInfo(text);

    // Replace line breaks with <br>
    text = text.replace(/\n/g, "<br>");

    return text;
}

// Censor contact information in messages
export function censorContactInfo(text) {
    if (!text) return "";

    // Phone number pattern: matches common phone number formats
    const phonePattern =
        /(\+\d{1,3}[-\.\s]?)?(\(?\d{3}\)?[-\.\s]?)?\d{3}[-\.\s]?\d{4}/g;

    // Email pattern: matches standard email formats
    const emailPattern = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;

    // URL pattern: matches web URLs, including those with and without protocol
    const urlPattern =
        /(https?:\/\/)?[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+([\/\?#].*)?/g;

    // Social media handles pattern: matches common social media username formats
    const socialPattern = /[@#][a-zA-Z0-9_]{2,}/g;

    // Replace contact information with asterisks
    let censoredText = text
        .replace(phonePattern, (match) => "*".repeat(match.length))
        .replace(emailPattern, (match) => "*".repeat(match.length))
        .replace(urlPattern, (match) => {
            // Preserve certain safe URLs (optional, remove if all URLs should be censored)
            if (match.includes("brandboost") || match.includes("yourplatform")) {
                return match;
            }
            return "*".repeat(match.length);
        })
        .replace(socialPattern, (match) => "*".repeat(match.length));

    return censoredText;
}

// Get cookie by name
export function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);

    if (parts.length === 2) return parts.pop().split(";").shift();
}

// Generate profile image or text placeholder HTML
export function generateProfileImage(imageUrl, name, elementId = "") {
    if (imageUrl && !imageUrl.includes("default-avatar.png")) {
        // Return image if valid image URL exists
        return `<img src="${imageUrl}" alt="Profile picture of ${name}" ${elementId ? `id="${elementId}"` : ""}>`;
    } else {
        // Generate text placeholder with first two letters
        const initials = getInitials(name);
        const backgroundColor = getColorFromName(name);

        return `<div class="profile-text-placeholder" ${elementId ? `id="${elementId}"` : ""} 
                style="background-color: ${backgroundColor}; display: flex; align-items: center; 
                justify-content: center; width: 100%; height: 100%; border-radius: 50%; 
                color: white; font-weight: bold; text-transform: uppercase;">${initials}</div>`;
    }
}

// Create consistent profile link HTML
export function createProfileLink(userId, content) {
    return `<a href="/user/${userId}" class="profile-link" data-user-id="${userId}">${content}</a>`;
}