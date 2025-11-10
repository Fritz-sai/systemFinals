document.addEventListener('DOMContentLoaded', () => {
	const sidebar = document.getElementById('adminSidebar');
	const toggle = document.getElementById('sidebarToggle');
	if (toggle && sidebar) {
		toggle.addEventListener('click', () => {
			const isHidden = sidebar.style.transform === 'translateX(-100%)';
			sidebar.style.transform = isHidden ? 'translateX(0)' : 'translateX(-100%)';
		});
		// Initialize for small screens
		if (window.innerWidth < 768) {
			sidebar.style.transform = 'translateX(-100%)';
		}
	}
});

;(function(){
	const sidebar = document.querySelector('.sidebar');
	const toggleBtn = document.getElementById('sidebarToggle');
	if (toggleBtn && sidebar) {
		toggleBtn.addEventListener('click', function(){
			const collapsed = sidebar.classList.toggle('sidebar--collapsed');
			this.textContent = collapsed ? '⟩' : '⟨';
		});
	}
	// Mobile open with "m" key
	document.addEventListener('keydown', function(e){
		if (e.key.toLowerCase() === 'm'){
			document.querySelector('.sidebar')?.classList.toggle('is-open');
		}
	});
	// Modal helpers
	window.openModal = function(id){
		const m = document.getElementById(id);
		if (m){ m.classList.add('is-open'); }
	}
	window.closeModal = function(id){
		const m = document.getElementById(id);
		if (m){ m.classList.remove('is-open'); }
	}
	// Export report (demo)
	window.exportReport = function(){
		alert('Exporting report... (hook up to server-side export)');
	}
})();


