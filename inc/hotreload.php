<?php
add_action('wp_head', 'inject_hotreload'); // Updated function name
function inject_hotreload() // Updated function name
{
?>
    <script>
        const broadcastChannel = new BroadcastChannel('css_update_channel');

        broadcastChannel.onmessage = (event) => {
            const {
                cssFilename,
                cssContent
            } = event.data;
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
        };
    </script>
<?php
}
?>