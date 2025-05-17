        function handleSubmit(event) {
            const errorMessage = document.getElementById('errorMessage');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (!username || !password) {
                event.preventDefault();
                errorMessage.style.display = 'block';
                return false;
            } else {
                errorMessage.style.display = 'none';
                return true;
            }
        }