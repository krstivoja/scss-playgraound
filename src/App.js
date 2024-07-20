import React, { useState, useEffect } from 'react';
import Editor from '@monaco-editor/react';
import { Snackbar, Button, Modal, TextControl } from '@wordpress/components'; // Import necessary components

const App = () => {
    const [files, setFiles] = useState([]);
    const [currentFile, setCurrentFile] = useState('');
    const [content, setContent] = useState('');
    const [errorOutput, setErrorOutput] = useState('');
    const [snackbarMessage, setSnackbarMessage] = useState(''); // State for Snackbar message
    const [isModalOpen, setIsModalOpen] = useState(false); // State for modal visibility
    const [newFileName, setNewFileName] = useState(''); // State for new file name

    useEffect(() => {
        fetch(scssPlayground.apiUrl + 'files')
            .then(response => response.json())
            .then(data => {
                setFiles(data);
                if (data.length > 0) {
                    loadFile(data[0]); // Load the first file
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
            }, 3000); // Hide Snackbar after 3 seconds if no error
        }
        return () => clearTimeout(timeout);
    }, [snackbarMessage, errorOutput]);

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
                    setErrorOutput(''); // Clear error output on success
                    setSnackbarMessage('SCSS file saved successfully'); // Set Snackbar message
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
                        setErrorOutput(''); // Clear error output on success
                        setSnackbarMessage('CSS file saved successfully'); // Set Snackbar message
                    }
                });
        } catch (error) {
            setErrorOutput(error.message);
            setSnackbarMessage('Error occurred while saving CSS file'); // Set Snackbar message for error
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
                                className={file === currentFile ? 'bg-slate-300' : ''}
                            >
                                <span onClick={() => loadFile(file)}>{file}</span>
                                <Button isDestructive onClick={() => deleteFile(file)}>Delete</Button> {/* Delete File Button */}
                            </li>
                        ))}
                    </ul>
                    <Button isPrimary onClick={() => setIsModalOpen(true)}>+ Add New</Button> {/* Add New File Button */}
                </div>

                <div className='flex-1'>
                    <Editor
                        height="30vh"
                        language="scss"
                        value={content}
                        onChange={(value) => setContent(value)}
                    />
                </div>
            </div>

            {snackbarMessage && (
                <Snackbar
                    onDismiss={() => setSnackbarMessage('')}
                    style={{ backgroundColor: errorOutput ? 'red' : 'black' }} // Set background color based on error presence
                >
                    <pre style={{ whiteSpace: 'pre-wrap' }}>
                        {errorOutput ? errorOutput : snackbarMessage}
                    </pre>
                </Snackbar>
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