const saveFile = (fileName, content) => {
    fetch(`${scssPlayground.ajax_url}/wp-json/scss-playground/v1/save-file`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include', // Ensure cookies are included in the request
        body: JSON.stringify({
            file_name: fileName,
            content: content
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server error: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            alert('File saved successfully');
        })
        .catch(error => {
            alert('Error saving file: ' + error.message);
        });
};

export default saveFile;
