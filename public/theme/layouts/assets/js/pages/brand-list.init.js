// public/assets/js/brands.init.js
var perPage = 10,
    editlist = false,
    checkAll = document.getElementById("checkAll"),
    options = {
        valueNames: ["id", "name", "logo", "categories", "products", "featured"],
        page: perPage,
        pagination: true,
        plugins: [ListPagination({ left: 2, right: 2 })]
    },
    brandList = new List("brandList", options);

checkAll && (checkAll.onclick = function () {
    document.querySelectorAll('.form-check-all input[type="checkbox"]').forEach(function (e) {
        e.checked = this.checked,
            e.closest("tr").classList.toggle("table-active", this.checked)
    }),
        toggleRemoveActions()
});

document.querySelectorAll('input[name="chk_child"]').forEach(e => {
    e.addEventListener("click", function () {
        this.closest("tr").classList.toggle("table-active", this.checked);
        toggleRemoveActions();
    })
});

function toggleRemoveActions() {
    var e = document.querySelectorAll('input[name="chk_child"]:checked').length;
    document.getElementById("remove-actions").classList.toggle("d-none", e === 0);
}

// Chart
var ctx = document.getElementById("brandChart");
ctx && new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Products',
            data: chartData,
            backgroundColor: '#405189'
        }]
    },
    options: {
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: false } }
    }
});

// Choices.js
var choicesInstance = new Choices('#categories_select', {
    removeItemButton: true,
    searchEnabled: true,
    placeholderValue: 'Select categories...'
});

// Modal & Form
var modal = new bootstrap.Modal(document.getElementById('showModal'));
var form = document.getElementById('brandForm');
var logoPreview = document.getElementById('logo_preview');

// Add Button
document.querySelector('.add-btn')?.addEventListener('click', resetForm);

function resetForm() {
    form.reset();
    document.getElementById('brand_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Brand';
    document.getElementById('submitBtn').textContent = 'Save Brand';
    logoPreview.style.display = 'none';
    choicesInstance.setValue([]);
}

// Edit
document.querySelectorAll('.edit-item-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        var id = this.dataset.id;
        axios.get(`/brands/${id}/edit`)
            .then(res => {
                var b = res.data;
                document.getElementById('brand_id').value = b.id;
                form.name.value = b.name;
                document.getElementById('is_featured').checked = b.is_featured;
                if (b.logo) {
                    logoPreview.src = b.logo;
                    logoPreview.style.display = 'block';
                }
                choicesInstance.setValue(b.categories.map(c => ({ value: c.id, label: c.name })));
                document.getElementById('modalTitle').textContent = 'Edit Brand';
                document.getElementById('submitBtn').textContent = 'Update Brand';
                modal.show();
            });
    });
});

// Form Submit
form.addEventListener('submit', function (e) {
    e.preventDefault();
    var id = document.getElementById('brand_id').value;
    var url = id ? `/brands/${id}` : '/brands';
    var formData = new FormData(form);
    if (id) formData.append('_method', 'PUT');

    axios.post(url, formData)
        .then(() => location.reload())
        .catch(err => {
            var msg = 'Error occurred';
            if (err.response?.status === 422) {
                msg = Object.values(err.response.data.errors).flat().join('<br>');
            }
            Swal.fire('Error!', msg, 'error');
        });
});

// Single Delete
document.querySelectorAll('.remove-item-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        var id = this.dataset.id;
        document.getElementById('delete-record').onclick = () => {
            axios.delete(`/brands/${id}`).then(() => location.reload());
        };
        new bootstrap.Modal('#deleteRecordModal').show();
    });
});

// Multiple Delete
function deleteMultiple() {
    var ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked'))
        .map(cb => cb.value);

    if (ids.length === 0) return;

    Swal.fire({
        title: 'Delete selected brands?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete!'
    }).then(result => {
        if (result.isConfirmed) {
            Promise.all(ids.map(id => axios.delete(`/brands/${id}`)))
                .then(() => location.reload());
        }
    });
}