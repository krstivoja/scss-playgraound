import React, { useState, useEffect } from 'react';
import Editor from '@monaco-editor/react';

const App = () => {
    const [files, setFiles] = useState([]);
    const [currentFile, setCurrentFile] = useState('');
    const [content, setContent] = useState('');
    const [errorOutput, setErrorOutput] = useState('');

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

    const saveFile = async () => {
        const formattedContent = content.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

        // Save SCSS file
        await fetch(scssPlayground.apiUrl + 'file', {
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
                    alert('SCSS file saved successfully');
                }
            });

        // Compile SCSS to CSS and save CSS file
        try {
            const sass = await import('https://jspm.dev/sass');
            const result = sass.compileString(content);
            const cssFilename = currentFile.replace(/\.scss$/, '.css');
            await fetch(scssPlayground.apiUrl + 'css-file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    filename: cssFilename,
                    content: result.css,
                }),
            }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('CSS file saved successfully');
                    }
                });
        } catch (error) {
            setErrorOutput(error.message);
        }
    };

    return (
        <div>
            <h1>SCSS Playground</h1>
            <div className="flex gap-12">
                <div className='w-1/4 bg-white'>
                    <ul>
                        {files.map(file => (
                            <li key={file} onClick={() => loadFile(file)}>{file}</li>
                        ))}
                    </ul>
                </div>

                <div className='flex-1'>
                    <Editor
                        height="30vh"
                        language="scss"
                        value={content}
                        onChange={(value) => setContent(value)}
                    />
                    <button onClick={saveFile}>Save</button>

                    <h2>Error Log</h2>
                    <textarea rows="5" cols="50" value={errorOutput} readOnly />
                </div>
            </div>
        </div>
    );
};

export default App;