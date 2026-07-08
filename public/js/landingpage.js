/* @author Romila Raluca */
    document.addEventListener('click', (e) => {
        if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A') {
            const ink = document.createElement('div');
            ink.style.position = 'absolute';
            ink.style.left = `${e.pageX - 10}px`;
            ink.style.top = `${e.pageY - 10}px`;
            ink.style.width = '20px';
            ink.style.height = '20px';
            ink.style.backgroundColor = 'rgba(157, 66, 44, 0.1)';
            ink.style.borderRadius = '50%';
            ink.style.pointerEvents = 'none';
            ink.style.zIndex = '1000';
            ink.style.transform = `scale(${Math.random() * 2 + 0.5})`;
            document.body.appendChild(ink);

            setTimeout(() => {
                ink.style.opacity = '0';
                ink.style.transition = 'opacity 1s ease';
                setTimeout(() => ink.remove(), 1000);
            }, 1500);
        }
    });
