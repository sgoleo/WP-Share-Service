document.addEventListener('DOMContentLoaded', function() {
	// Search and Filter logic for [sgoplus_files]
	const searchInput = document.getElementById('sgoplus-fs-search-field');
	const catFilter = document.getElementById('sgoplus-fs-cat-filter');
	const cards = document.querySelectorAll('.sgoplus-fs-file-card');

	function filterFiles() {
		if (!searchInput || !catFilter) return;
		const term = searchInput.value.toLowerCase();
		const cat = catFilter.value;

		cards.forEach(card => {
			const titleElement = card.querySelector('.sgoplus-fs-card-title');
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
	const logToggles = document.querySelectorAll('.sgoplus-fs-log-toggle');
	logToggles.forEach(btn => {
		btn.addEventListener('click', function() {
			const card = this.closest('.sgoplus-fs-file-card');
			const overlay = card.querySelector('.sgoplus-fs-changelog-overlay');
			if (overlay) overlay.classList.toggle('active');
		});
	});

	const closeLogBtns = document.querySelectorAll('.sgoplus-fs-close-log');
	closeLogBtns.forEach(btn => {
		btn.addEventListener('click', function() {
			const overlay = this.closest('.sgoplus-fs-changelog-overlay');
			if (overlay) overlay.classList.remove('active');
		});
	});
});
