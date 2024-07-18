import React, { useEffect, useState } from 'react';
import Editor from './components/Editor';

const App = () => {
    const [files, setFiles] = useState([]);

    useEffect(() => {
        const fetchFiles = async () => {
            try {
                const response = await fetch('/wp-json/scss-playground/v1/files');
                const data = await response.json();
                setFiles(data);
            } catch (error) {
                console.error('Error fetching files:', error);
            }
        };

        fetchFiles();
    }, []);

    return (
        <div>
            <h1>My App</h1>
            <ul>
                {files.map((file) => (
                    <li key={file}>{file}</li>
                ))}
            </ul>
            <Editor />
        </div>
    );
};

export default App;