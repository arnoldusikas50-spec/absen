// Function untuk melakukan AJAX GET request menggunakan fetch
function ajaxGet(url, callback) {
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => callback(null, data))
        .catch(error => callback(error, null)); // Tangani error jaringan atau parsing
}

// Function untuk melakukan AJAX POST request menggunakan fetch
async function post(url, formData) {
    return new Promise((resolve, reject) => {
        setTimeout(function () {
            $.ajax({
                url: url,
                method: "POST",
                processData: false,
                contentType: false,
                data: formData,
                success: function (respon) {
                    resolve(respon);
                },
                error: function (xhr, status, error) {
                    $('#loading').addClass('d-none');
                    console.log(xhr);
                    console.log(status);
                    console.log(error);
                    Swal.fire({
                        title: "Gagal",
                        html: xhr.responseText,
                        icon: "error"
                    });
                    reject(error);
                }
            });
        }, 500)
    });
}

async function ajaxPost(url, data, callback) {
    post(url, data).then(data => callback(null, data)).catch(error => callback(error, null));
}