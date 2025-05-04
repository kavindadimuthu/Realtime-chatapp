import * as Utils from './Utils.js';

// Manages user profile functionality and UI
export default class ProfileManager {
    constructor(config) {
        this.ui = config.ui;
        this.websocket = config.websocket;
        this.conversation = config.conversation;
        
        // DOM elements
        this.profileSidebar = document.getElementById('profile-sidebar');
        this.closeProfileBtn = document.getElementById('close-profile-btn');
        this.profileLoading = document.getElementById('profile-loading');
        this.profileData = document.getElementById('profile-data');
        this.profileError = document.getElementById('profile-error');
        this.retryProfileBtn = document.getElementById('retry-profile-btn');
        this.messageUserBtn = document.getElementById('message-user-btn');
        
        // Profile details elements
        this.profileCover = document.getElementById('profile-cover');
        this.profileAvatarLarge = document.getElementById('profile-avatar-large');
        this.profileName = document.getElementById('profile-name');
        this.profileEmail = document.getElementById('profile-email');
        this.profilePhone = document.getElementById('profile-phone');
        this.profileJoined = document.getElementById('profile-joined');
        this.profileBio = document.getElementById('profile-bio');
        
        // Ensure profile sidebar is hidden on initialization
        if (this.profileSidebar) {
            this.profileSidebar.classList.add('hidden');
        }
    }

    // Initialize event listeners
    init() {
        // Ensure profile sidebar is hidden
        this.hideProfileSidebar();
        
        // Close profile sidebar when close button is clicked
        this.closeProfileBtn.addEventListener('click', () => {
            this.hideProfileSidebar();
        });
        
        // Setup profile link click handler with improved event delegation
        document.addEventListener('click', (e) => {
            // Find the closest profile link element or avatar link
            const profileLink = e.target.closest('.profile-link') || e.target.closest('.avatar a');
            
            if (profileLink) {
                e.preventDefault();
                e.stopPropagation();
                
                // Get user ID from data attribute if available, otherwise from URL
                let userId = profileLink.dataset.userId;
                
                // If no data attribute, try to get from href
                if (!userId && profileLink.getAttribute('href')) {
                    const href = profileLink.getAttribute('href');
                    userId = href.split('/').pop();
                }
                
                if (userId) {
                    this.showProfileSidebar(userId);
                }
            }
        });

        // Add resize handler
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                // Update grid layout based on sidebar visibility
                this.ui.updateContainerGrid(!this.profileSidebar.classList.contains('hidden'));
            }
        });
    }
    
    // Show profile sidebar and load user data
    showProfileSidebar(userId) {
        // Show loading state
        this.profileLoading.style.display = 'flex';
        this.profileData.style.display = 'none';
        this.profileError.style.display = 'none';
        
        // Remove hidden class to show the sidebar
        this.profileSidebar.classList.remove('hidden');
        
        // For desktop layouts, update the grid
        if (window.innerWidth > 768) {
            this.ui.updateContainerGrid(true);
        }
        
        // Create overlay on mobile
        if (window.innerWidth <= 768) {
            this.ui.createOverlay('profile-overlay', () => {
                this.hideProfileSidebar();
            });
        }
        
        // Fetch user profile data
        this.fetchUserProfile(userId);
    }
    
    // Show the current user's profile for viewing/editing
    showSelfProfile() {
        // Show loading state
        this.profileLoading.style.display = 'flex';
        this.profileData.style.display = 'none';
        this.profileError.style.display = 'none';
        
        // Remove hidden class to show the sidebar
        this.profileSidebar.classList.remove('hidden');
        
        // For desktop layouts, update the grid
        if (window.innerWidth > 768) {
            this.ui.updateContainerGrid(true);
        }
        
        // Create overlay on mobile
        if (window.innerWidth <= 768) {
            this.ui.createOverlay('profile-overlay', () => {
                this.hideProfileSidebar();
            });
        }
        
        // Fetch current user's profile data
        this.fetchSelfProfile();
    }
    
    // Hide profile sidebar
    hideProfileSidebar() {
        // Add hidden class to hide the sidebar
        this.profileSidebar.classList.add('hidden');
        
        // For desktop layouts, update the grid
        if (window.innerWidth > 768) {
            this.ui.updateContainerGrid(false);
        }
        
        // If on mobile, remove any overlay
        if (window.innerWidth <= 768) {
            this.ui.removeOverlay('profile-overlay');
        }
    }
    
    // Fetch user profile data from API
    fetchUserProfile(userId) {
        fetch(`/api/user/profile?id=${userId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                this.updateProfileUI(data.data);
                this.profileLoading.style.display = 'none';
                this.profileData.style.display = 'block';
            } else {
                throw new Error('Failed to load profile data');
            }
        })
        .catch(error => {
            console.error('Error loading profile:', error);
            this.profileLoading.style.display = 'none';
            this.profileError.style.display = 'flex';
            
            // Setup retry button
            this.retryProfileBtn.onclick = () => {
                this.showProfileSidebar(userId);
            };
        });
    }
    
    // Fetch the current logged-in user's profile
    fetchSelfProfile() {
        fetch('/api/user/profile', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                this.updateProfileUI(data.data, true); // true indicates self-profile
                this.profileLoading.style.display = 'none';
                this.profileData.style.display = 'block';
                
                // Add edit profile button for self-profile
                this.addEditProfileButton();
            } else {
                throw new Error('Failed to load profile data');
            }
        })
        .catch(error => {
            console.error('Error loading profile:', error);
            this.profileLoading.style.display = 'none';
            this.profileError.style.display = 'flex';
            
            // Setup retry button
            this.retryProfileBtn.onclick = () => {
                this.showSelfProfile();
            };
        });
    }
    
    // Update profile UI with user data
    updateProfileUI(userData, isSelf = false) {
        // Set cover image if available
        if (userData.cover_picture) {
            this.profileCover.innerHTML = 
                `<img src="${userData.cover_picture}" alt="Cover image">`;
        } else {
            this.profileCover.innerHTML = 
                `<div class="profile-cover-placeholder">
                    <i class="fas fa-image"></i>
                </div>`;
        }
        
        // Set profile picture
        if (userData.profile_picture) {
            this.profileAvatarLarge.innerHTML = `<img src="${userData.profile_picture}" alt="Profile picture">`;
        } else {
            const initials = Utils.getInitials(userData.name);
            const bgColor = Utils.getColorFromName(userData.name);
            this.profileAvatarLarge.innerHTML = 
                `<div class="profile-text-placeholder" 
                  style="background-color: ${bgColor};">${initials}</div>`;
        }
        
        // Set user details
        this.profileName.textContent = userData.name || 'User';
        this.profileEmail.textContent = userData.email || 'No email provided';
        this.profilePhone.textContent = userData.phone || 'Not provided';
        
        // Format and set join date
        const joinDate = userData.created_at ? 
            new Date(userData.created_at).toLocaleDateString('en-US', {
                year: 'numeric', 
                month: 'long', 
                day: 'numeric'
            }) : 'Unknown';
        this.profileJoined.textContent = joinDate;
        
        // Set bio
        this.profileBio.textContent = userData.bio || 'No bio provided.';
        
        // Handle self vs other profile differences
        if (isSelf) {
            // Hide message button for self profile
            if (this.messageUserBtn) {
                this.messageUserBtn.style.display = 'none';
            }
            // Add edit profile button
            this.addEditProfileButton();
        } else {
            // Setup message button functionality for other users
            if (this.messageUserBtn) {
                this.messageUserBtn.style.display = 'block';
                this.setupMessageButton(userData);
            }
        }
    }
    
    // Setup message button functionality
    setupMessageButton(userData) {
        this.messageUserBtn.onclick = () => {
            // Close profile sidebar
            this.hideProfileSidebar();
            
            // Find existing conversation or start new one with this user
            const conversationItem = document.querySelector(`.message-item[data-user-id="${userData.user_id}"]`);
            if (conversationItem) {
                conversationItem.click();
            } else {
                // Trigger new conversation flow through existing handlers
                document.getElementById('new-chat-btn').click();
                // Set email in search field
                setTimeout(() => {
                    document.getElementById('user-email-search').value = userData.email;
                    document.getElementById('user-email-search').dispatchEvent(new Event('input'));
                }, 300);
            }
        };
    }

    // Add edit profile button to self-profile view
    addEditProfileButton() {
        // Remove message button if it exists
        if (this.messageUserBtn) {
            this.messageUserBtn.style.display = 'none';
        }
        
        // Create edit profile button container if it doesn't exist
        let profileActions = document.querySelector('.profile-actions');
        if (!profileActions) {
            profileActions = document.createElement('div');
            profileActions.className = 'profile-actions';
            document.querySelector('.profile-data').appendChild(profileActions);
        }
        
        // Check if edit button already exists
        let editButton = document.getElementById('edit-profile-btn');
        if (!editButton) {
            editButton = document.createElement('button');
            editButton.id = 'edit-profile-btn';
            editButton.className = 'edit-profile-btn';
            editButton.innerHTML = '<i class="fas fa-pencil-alt"></i> Edit Profile';
            profileActions.appendChild(editButton);
        } else {
            editButton.style.display = 'block';
        }
        
        // Add click event to edit button
        editButton.onclick = () => {
            this.switchToEditMode();
        };
    }
    
    // Switch to profile edit mode
    switchToEditMode() {
        // Create edit form if it doesn't exist
        let editForm = document.getElementById('edit-profile-form');
        if (!editForm) {
            // Get current profile data
            const name = this.profileName.textContent;
            const phone = this.profilePhone.textContent === 'Not provided' ? '' : this.profilePhone.textContent;
            const bio = this.profileBio.textContent === 'No bio provided.' ? '' : this.profileBio.textContent;
            
            // Create form container
            editForm = document.createElement('div');
            editForm.id = 'edit-profile-form';
            editForm.className = 'edit-profile-form';
            
            // Create form content
            editForm.innerHTML = `
                <div class="edit-profile-field">
                    <label for="edit-name">Name</label>
                    <input type="text" id="edit-name" value="${name}" maxlength="50">
                </div>
                <div class="edit-profile-field">
                    <label for="edit-phone">Phone</label>
                    <input type="tel" id="edit-phone" value="${phone}" maxlength="15">
                </div>
                <div class="edit-profile-field">
                    <label for="edit-bio">Bio</label>
                    <textarea id="edit-bio" maxlength="500" rows="4">${bio}</textarea>
                </div>
                <div class="edit-profile-field">
                    <label>Profile Picture</label>
                    <input type="file" id="edit-profile-picture" accept="image/*">
                </div>
                <div class="edit-profile-field">
                    <label>Cover Picture</label>
                    <input type="file" id="edit-cover-picture" accept="image/*">
                </div>
                <div class="edit-profile-actions">
                    <button type="button" id="cancel-edit-btn" class="cancel-edit-btn">Cancel</button>
                    <button type="button" id="update-profile-btn" class="update-profile-btn">Update Profile</button>
                </div>
            `;
            
            // Replace profile details with edit form
            const profileDetails = document.querySelector('.profile-details');
            profileDetails.parentNode.insertBefore(editForm, profileDetails);
            profileDetails.style.display = 'none';
            
            // Hide bio section and edit button while in edit mode
            document.querySelector('.profile-bio-section').style.display = 'none';
            document.getElementById('edit-profile-btn').style.display = 'none';
        } else {
            // Show the form if it already exists but is hidden
            editForm.style.display = 'block';
            document.querySelector('.profile-details').style.display = 'none';
            document.querySelector('.profile-bio-section').style.display = 'none';
            document.getElementById('edit-profile-btn').style.display = 'none';
        }
        
        // Add event listeners to buttons
        document.getElementById('cancel-edit-btn').addEventListener('click', () => {
            this.cancelEditMode();
        });
        
        document.getElementById('update-profile-btn').addEventListener('click', () => {
            this.updateProfile();
        });
    }
    
    // Cancel edit mode and return to view mode
    cancelEditMode() {
        const editForm = document.getElementById('edit-profile-form');
        if (editForm) {
            editForm.style.display = 'none';
        }
        
        // Show profile details again
        document.querySelector('.profile-details').style.display = 'block';
        document.querySelector('.profile-bio-section').style.display = 'block';
        document.getElementById('edit-profile-btn').style.display = 'block';
    }
    
    // Update profile with form data
    updateProfile() {
        // Get form data
        const name = document.getElementById('edit-name').value;
        const phone = document.getElementById('edit-phone').value;
        const bio = document.getElementById('edit-bio').value;
        const profilePicture = document.getElementById('edit-profile-picture').files[0];
        const coverPicture = document.getElementById('edit-cover-picture').files[0];
        
        // Create FormData object
        const formData = new FormData();
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('bio', bio);
        
        if (profilePicture) {
            formData.append('profile_picture', profilePicture);
        }
        
        if (coverPicture) {
            formData.append('cover_picture', coverPicture);
        }
        
        // Show loading state
        const updateButton = document.getElementById('update-profile-btn');
        updateButton.disabled = true;
        updateButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        // Send update request
        fetch('/api/user/profile', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                this.ui.showSuccessToast('Profile updated successfully');
                
                // Update UI with new data
                this.updateProfileUI(data.data, true);
                
                // Exit edit mode
                this.cancelEditMode();
                
                // Update header profile image if it changed
                if (data.data.profile_picture) {
                    this.ui.updateUserProfile({
                        name: data.data.name,
                        avatar: data.data.profile_picture
                    });
                }
            } else {
                throw new Error(data.error || 'Update failed');
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            this.ui.showErrorToast('Failed to update profile: ' + error.message);
        })
        .finally(() => {
            // Re-enable button
            updateButton.disabled = false;
            updateButton.innerHTML = 'Update Profile';
        });
    }

    // Generate consistent profile image HTML
    generateProfileImageHtml(userData, elementId = null) {
        const { userId, name, avatar } = userData;
        
        // Create container
        const container = document.createElement('a');
        container.href = `/user/${userId}`;
        container.className = 'profile-link';
        container.setAttribute('data-user-id', userId);
        
        if (avatar && !avatar.includes('default-avatar.png')) {
            // Real image
            const img = document.createElement('img');
            img.src = avatar;
            img.alt = `Profile picture of ${name}`;
            if (elementId) img.id = elementId;
            container.appendChild(img);
        } else {
            // Text placeholder
            const initials = Utils.getInitials(name);
            const bgColor = Utils.getColorFromName(name);
            
            const placeholder = document.createElement('div');
            placeholder.className = 'profile-text-placeholder';
            placeholder.style.backgroundColor = bgColor;
            placeholder.textContent = initials;
            if (elementId) placeholder.id = elementId;
            
            container.appendChild(placeholder);
        }
        
        return container;
    }
}