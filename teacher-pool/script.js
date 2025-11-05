document.addEventListener('DOMContentLoaded', function() {
    AOS && AOS.init && AOS.init({ duration: 800, easing: 'ease-in-out', once: true, offset: 100 });
    loadFacultyList();
});

async function loadFacultyList(query = '') {
    const facultyListDiv = document.getElementById('faculty-list');
    if (!facultyListDiv) return;

    facultyListDiv.innerHTML = '<p class="text-center">Loading faculty...</p>';

    try {
        const url = 'faculty-list.php' + (query ? ('?q=' + encodeURIComponent(query)) : '');
        const res = await fetch(url);
        if (!res.ok) throw new Error('Network response was not ok');

        const payload = await res.json();
        if (!payload.success) {
            facultyListDiv.innerHTML = `<p class="text-center text-danger">Failed to load faculty: ${payload.error || 'unknown'}</p>`;
            return;
        }

        const data = payload.data;
        if (!data.length) {
            facultyListDiv.innerHTML = '<p class="text-center text-muted">No faculty found.</p>';
            return;
        }

        facultyListDiv.innerHTML = '';
        data.forEach(item => {
            const card = document.createElement('div');
            card.className = 'faculty-card bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:scale-105 flex flex-col items-center p-4';

            const img = document.createElement('img');
            img.src = 'https://via.placeholder.com/128x128.png?text=Photo';
            img.alt = item.name;
            img.className = 'w-32 h-32 rounded-full object-cover mb-4 border-4 border-primary-600 shadow-md';
            img.onerror = function(){ this.onerror=null; this.src='https://via.placeholder.com/128x128.png?text=No+Image'; };

            const name = document.createElement('h3');
            name.className = 'text-xl font-semibold text-gray-800';
            name.textContent = item.name;

            const email = document.createElement('p');
            email.className = 'text-sm text-gray-600 mt-1';
            email.textContent = item.email || '';

            card.appendChild(img);
            card.appendChild(name);
            card.appendChild(email);

            facultyListDiv.appendChild(card);
        });

    } catch (err) {
        console.error('Error loading faculty:', err);
        facultyListDiv.innerHTML = `<p class="text-center text-danger">Error loading faculty list.</p>`;
    }
}

const facultySearch = document.getElementById('faculty-search');
if (facultySearch) {
    let timeout = null;
    facultySearch.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            loadFacultyList(this.value.trim());
        }, 300);
    });
}