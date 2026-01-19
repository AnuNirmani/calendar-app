            <!-- Footer -->
            <div class="mt-10 pt-6 border-t border-gray-300">
                <footer class="footer" style=" text-align: center;">
            &copy; <?php echo date('Y'); ?> Developed and Maintained by WNL in collaboration with Web Publishing Department <br>
            © All rights reserved, 2008 - Wijeya Newspapers Ltd.
        </footer>
            </div>
            
            </div><!-- End Content Area -->
        </main>
    </div>

    <!-- Mobile Menu Script -->
    <script>
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const closeSidebar = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const mobileHeader = document.getElementById('mobile-header');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            mobileHeader.classList.add('-translate-y-full');
        }

        function closeSidebarMenu() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            mobileHeader.classList.remove('-translate-y-full');
        }

        mobileMenuBtn.addEventListener('click', openSidebar);
        closeSidebar.addEventListener('click', closeSidebarMenu);
        overlay.addEventListener('click', closeSidebarMenu);
    </script>
</body>
</html>
