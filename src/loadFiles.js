const loadFiles = () => {
    return fetch(`${scssPlayground.ajax_url}/wp-json/scss-playground/v1/load-files`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include' // Ensure cookies are included in the request
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server error: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            return data;
        })
        .catch(error => {
            throw new Error('Error loading files: ' + error.message);
        });
};

export default loadFiles;
