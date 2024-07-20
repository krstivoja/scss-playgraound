import React, { useState, useEffect } from 'react';
import Editor from '@monaco-editor/react';

const App = () => {
    const [files, setFiles] = useState([]);
    const [currentFile, setCurrentFile] = useState('');
    const [content, setContent] = useState('');

    useEffect(() => {
        fetch(scssPlayground.apiUrl + 'files')
            .then(response => response.json())
            .then(data => setFiles(data));
    }, []);

    const loadFile = (file) => {
        fetch(scssPlayground.apiUrl + 'file/' + file)
            .then(response => response.text())
            .then(data => {
                const formattedContent = data.replace(/\\n/g, '\n').replace(/\\r/g, '\r').replace(/^"|"$/g, '');
                setCurrentFile(file);
                setContent(formattedContent);
            });
    };

    const saveFile = () => {
        const formattedContent = content.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        fetch(scssPlayground.apiUrl + 'file', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                filename: currentFile,
                content: formattedContent,
            }),
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('File saved successfully');
                }
            });
    };

    return (
        <div>
            <h1>SCSS Playground</h1>
            <div>
                <h2>Files</h2>
                <ul>
                    {files.map(file => (
                        <li key={file} onClick={() => loadFile(file)}>{file}</li>
                    ))}
                </ul>
            </div>
            <div>
                <h2>Editor</h2>
                <Editor
                    height="30vh"
                    language="scss"
                    value={content}
                    onChange={(value) => setContent(value)}
                />
                <button onClick={saveFile}>Save</button>
            </div>
        </div>
    );
};

export default App;
