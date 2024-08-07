import React, { useState, useEffect } from 'react';
import Editor from '@monaco-editor/react';
import { Snackbar, Button, Modal, TextControl } from '@wordpress/components';

const App = () => {
    const [files, setFiles] = useState([]);
    const [currentFile, setCurrentFile] = useState('');
    const [content, setContent] = useState('');
    const [errorOutput, setErrorOutput] = useState('');
    const [snackbarMessage, setSnackbarMessage] = useState('');
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [newFileName, setNewFileName] = useState('');
    const broadcastChannel = new BroadcastChannel('css_update_channel');
    const isAdmin = true; // Set this to true if the user is an admin

    useEffect(() => {
        fetch(scssPlayground.apiUrl + 'files')
            .then(response => response.json())
            .then(data => {
                setFiles(data);
                if (data.length > 0) {
                    loadFile(data[0]);
                }
            });
    }, []);

    useEffect(() => {
        const handleKeyDown = (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                event.preventDefault();
                saveFile();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [content, currentFile]);

    useEffect(() => {
        let timeout;
        if (snackbarMessage && !errorOutput) {
            timeout = setTimeout(() => {
                setSnackbarMessage('');
            }, 3000);
        }
        return () => clearTimeout(timeout);
    }, [snackbarMessage, errorOutput]);

    useEffect(() => {
        broadcastChannel.onmessage = (event) => {
            const { cssFilename, cssContent } = event.data;

            // Check if the user is not an admin before injecting CSS
            if (!isAdmin) { // Assuming isAdmin is a boolean indicating the user's role
                const styleSheet = document.querySelector(`link[href="/css/${cssFilename}"]`);

                if (styleSheet) {
                    const newStyle = document.createElement('style');
                    newStyle.innerHTML = cssContent;
                    document.head.appendChild(newStyle);
                    document.head.removeChild(styleSheet);
                } else {
                    const newLink = document.createElement('link');
                    newLink.rel = 'stylesheet';
                    newLink.href = `data:text/css;base64,${btoa(cssContent)}`;
                    document.head.appendChild(newLink);
                }
            }
        };
    }, []);

    const loadFile = (file) => {
        fetch(scssPlayground.apiUrl + 'file/' + file)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text(); // Ensure we get plain text
            })
            .then(data => {
                // Replace escape sequences with actual characters
                const formattedData = data
                    .replace(/\\n/g, '\n') // Replace \n with actual new lines
                    .replace(/\\\//g, '/') // Replace \/ with /
                    .replace(/"/g, ''); // Remove surrounding quotes if necessary

                setCurrentFile(file);
                setContent(formattedData); // Set the formatted content

                // console.log(formattedData); // This should log the correctly formatted content
            })
            .catch(error => {
                console.error('Error loading file:', error);
            });
    };

    const saveFile = async () => {
        const formattedContent = content.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

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
                    setErrorOutput('');
                    setSnackbarMessage('SCSS file saved successfully');
                }
            });

        try {
            const sass = await import('https://jspm.dev/sass');
            const result = sass.compileString(content, { style: 'compressed' }); // Use 'compressed' style for minification
            const cssFilename = currentFile.replace(/\.scss$/, '.css');

            // Ensure the CSS is a single line
            const singleLineCss = result.css.replace(/\n/g, '').replace(/\s+/g, ' ').trim();

            await fetch(scssPlayground.apiUrl + 'css-file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    filename: cssFilename,
                    content: singleLineCss, // Use the single line CSS
                }),
            }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setErrorOutput('');
                        setSnackbarMessage('CSS file saved successfully');
                        broadcastChannel.postMessage({ cssFilename, cssContent: singleLineCss });
                    }
                });
        } catch (error) {
            setErrorOutput(error.message);
            setSnackbarMessage('Error occurred while saving CSS file');
        }
    };
    const createNewFile = () => {
        if (newFileName) {
            setFiles([...files, newFileName]);
            setCurrentFile(newFileName);
            setContent('');
            setIsModalOpen(false);
            setNewFileName('');
        }
    };

    const deleteFile = async (file) => {
        if (window.confirm(`Do you want to delete "${file}"?`)) {
            await fetch(scssPlayground.apiUrl + 'file/' + file, {
                method: 'DELETE',
            }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setFiles(files.filter(f => f !== file));
                        if (currentFile === file) {
                            setCurrentFile('');
                            setContent('');
                        }
                        setSnackbarMessage('File deleted successfully');
                    } else {
                        setSnackbarMessage('Error deleting file');
                    }
                });
        }
    };

    return (
        <div>
            <h1 className='text-5xl font-bold my-8'>SCSS Playground</h1>
            <div className="flex gap-12">
                <div className='w-1/4 bg-white'>
                    <ul>
                        {files.map(file => (
                            <li
                                key={file}
                                className={`flex justify-between items-center border border-slate-300 ${file === currentFile ? 'bg-slate-300' : ''}`}
                            >
                                <span className='cursor-pointer flex-1' onClick={() => loadFile(file)}>{file}</span>
                                <Button isDestructive onClick={() => deleteFile(file)}>Delete</Button>
                            </li>
                        ))}
                    </ul>
                    <Button isPrimary onClick={() => setIsModalOpen(true)}>+ Add New</Button>
                </div>

                <div className='flex-1 border border-black border-solid'>
                    <Editor
                        height={window.innerHeight - 150}
                        language="scss"
                        value={content}
                        onChange={(value) => setContent(value)}
                    />
                </div>
            </div>

            {snackbarMessage && (
                <div className='fixed bottom-0 right-0 m-4'>
                    <Snackbar
                        onDismiss={() => setSnackbarMessage('')}
                        style={{ backgroundColor: errorOutput ? 'red' : 'black' }}
                    >
                        <pre style={{ whiteSpace: 'pre-wrap' }}>
                            {errorOutput ? errorOutput : snackbarMessage}
                        </pre>
                    </Snackbar>
                </div>
            )}

            {isModalOpen && (
                <Modal
                    title="Create New File"
                    onRequestClose={() => setIsModalOpen(false)}
                >
                    <TextControl
                        label="File Name"
                        value={newFileName}
                        onChange={(value) => setNewFileName(value)}
                    />
                    <Button isPrimary onClick={createNewFile}>Create</Button>
                </Modal>
            )}
        </div>
    );
};

export default App;