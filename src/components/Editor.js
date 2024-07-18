import React, { useState } from 'react';
import MonacoEditor from '@monaco-editor/react';

const Editor = () => {
    const [content, setContent] = useState('');

    const handleEditorChange = (value, event) => {
        setContent(value);
    };

    const handleSave = async () => {
        try {
            const response = await fetch('/wp-json/scss-playground/v1/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ content }),
            });
            if (response.ok) {
                alert('File saved successfully');
            } else {
                alert('Failed to save file');
            }
        } catch (error) {
            console.error('Error saving file:', error);
            alert('Error saving file');
        }
    };

    return (
        <div>
            <MonacoEditor
                height="80vh"
                defaultLanguage="scss"
                defaultValue="// Start typing your SCSS code here..."
                value={content}
                onChange={handleEditorChange}
            />
            <button onClick={handleSave}>Save</button>
        </div>
    );
};

export default Editor;