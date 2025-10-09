  $('#categoriesTable tbody tr').each(function() {
                    let rowId = $(this).attr('id') ? $(this).attr('id').replace('category-', '') : '';
                    let categoryName = $(this).find('td:nth-child(2)').text().trim();
                    
                    // Skip the current row if we're editing
                    if (currentId && rowId === currentId) return true;
                    
                    if (categoryName.toLowerCase() === value.toLowerCase().trim()) {
                        isDuplicate = true;
                        return false; // break the loop
                    }
                });
                
                return !isDuplicate;
            }, "This category name already exists");

            // Real-time validation on input change
            $('#categoryName').on('input', function() {
                $(this).valid();
            });

            // Reset validation when cancel button is clicked
            $('#cancelButton').on('click', function() {
                resetForm();
                $("#categoryForm").validate().resetForm();
            });
        });

        function editCategory(id, name) {
            currentEditId = id;
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('buttonText').textContent = 'Update Category';
            document.getElementById('cancelButton').style.display = 'inline-block';
            document.getElementById('categoryName').focus();
            
            // Trigger validation to clear any previous errors
            $("#categoryForm").validate().resetForm();
        }

        function toggleStatus(categoryId, currentStatus) {
            if (confirm('Are you sure you want to ' + (currentStatus === 'active' ? 'deactivate' : 'activate') + ' this category?')) {
                window.location.href = '?toggle_status=' + categoryId;
            }
        }

        function showDeleteModal(id, name) {
            currentDeleteId = id;
            document.getElementById('deleteCategoryName').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function confirmDelete() {
            if (currentDeleteId) {
                window.location.href = '?delete=' + currentDeleteId + '&confirm=yes';
            }
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            currentDeleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

        function resetForm() {
            currentEditId = null;
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryName').value = '';
            document.getElementById('buttonText').textContent = 'Create Category';
            document.getElementById('cancelButton').style.display = 'none';
        }

        function addNewPost() {
            window.location.href = 'add_post.html';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        });

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(msg => {
                msg.classList.remove('show');
            });
        }, 5000);
    </script>
</body>
</html>