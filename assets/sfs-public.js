document.addEventListener('DOMContentLoaded', function() {
	// Search and Filter logic for [sgoplus_files]
	const searchInput = document.getElementById('sfs-search-field');
	const catFilter = document.getElementById('sfs-cat-filter');
	const cards = document.querySelectorAll('.sfs-file-card');

	function filterFiles() {
		if (!searchInput || !catFilter) return;
		const term = searchInput.value.toLowerCase();
		const cat = catFilter.value;

		cards.forEach(card => {
			const titleElement = card.querySelector('.sfs-card-title');
			if (!titleElement) return;
			
			const title = titleElement.textContent.toLowerCase();
			const cardCats = (card.getAttribute('data-categories') || '').split(',');
			
			const matchesSearch = title.includes(term);
			const matchesCat = (cat === 'all' || cardCats.includes(cat));

			if (matchesSearch && matchesCat) {
				card.style.display = 'flex';
			} else {
				card.style.display = 'none';
			}
		});
	}

	if (searchInput) searchInput.addEventListener('input', filterFiles);
	if (catFilter) catFilter.addEventListener('change', filterFiles);

	// Log Toggle logic for [sgoplus_file]
	const logToggles = document.querySelectorAll('.sfs-log-toggle');
	logToggles.forEach(btn => {
		btn.addEventListener('click', function() {
			const card = this.closest('.sfs-file-card');
			const overlay = card.querySelector('.sfs-changelog-overlay');
			if (overlay) overlay.classList.toggle('active');
		});
	});

	const closeLogBtns = document.querySelectorAll('.sfs-close-log');
	closeLogBtns.forEach(btn => {
		btn.addEventListener('click', function() {
			const overlay = this.closest('.sfs-changelog-overlay');
			if (overlay) overlay.classList.remove('active');
		});
	});
});
