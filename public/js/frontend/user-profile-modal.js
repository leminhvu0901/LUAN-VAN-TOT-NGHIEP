    (function () {
        const $ = (id) => document.getElementById(id);

        /* ---- User Profile Modal ---- */
        const openUserProfile = () => {
            const modal = $('user-profile-modal');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        };
        const closeUserProfile = () => {
            const modal = $('user-profile-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        };
        
        $('account-btn')?.addEventListener('click', (e) => {
            e.preventDefault();
            openUserProfile();
        });
        $('close-user-profile')?.addEventListener('click', closeUserProfile);
        $('user-profile-overlay')?.addEventListener('click', closeUserProfile);
    })();
