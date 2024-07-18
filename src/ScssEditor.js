import React, { useRef, useState, useEffect } from 'react';
import Editor from '@monaco-editor/react';
import saveFile from './saveFile';
import loadFiles from './loadFiles';

const ScssEditor = ({ width = "800px", height = "600px", language = "scss", theme = "vs-dark" }) => {
    const [fileName, setFileName] = useState('');
    const [fileContent, setFileContent] = useState('');
    const [fileList, setFileList] = useState([]);
    const editorRef = useRef(null);

    useEffect(() => {
        loadFiles()
            .then(files => setFileList(files))
            .catch(error => console.error('Error loading files:', error));
    }, []);

    function handleEditorDidMount(editor) {
        editorRef.current = editor;
    }

    const handleSave = () => {
        if (fileName) {
            const content = editorRef.current.getValue();
            saveFile(fileName, content);
        } else {
            alert('Please enter a file name.');
        }
    };

    const handleFileSelect = (e) => {
        const selectedFile = e.target.value;
        if (selectedFile) {
            fetch(`${scssPlayground.ajax_url}?action=load_scss_file&file_name=${selectedFile}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': scssPlayground.nonce
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setFileName(selectedFile);
                        setFileContent(data.content);
                        editorRef.current.setValue(data.content);
                    } else {
                        alert('Error loading file: ' + data.data);
                    }
                })
                .catch(error => {
                    console.error('Error loading file:', error);
                    alert('Error loading file: ' + error.message);
                });
        }
    };

    return (
        <div>
            <div>
                <label htmlFor="fileName">File Name: </label>
                <input
                    type="text"
                    id="fileName"
                    value={fileName}
                    onChange={(e) => setFileName(e.target.value)}
                />
                <button onClick={handleSave}>Save</button>
            </div>
            <div>
                <label htmlFor="fileSelect">Select File: </label>
                <select id="fileSelect" onChange={handleFileSelect}>
                    <option value="">Select a file</option>
                    {fileList.map(file => (
                        <option key={file} value={file}>{file}</option>
                    ))}
                </select>
            </div>
            <Editor
                height={height}
                width={width}
                language={language}
                theme={theme}
                value={fileContent}
                onMount={handleEditorDidMount}
            />
        </div>
    );
};

export default ScssEditor;
