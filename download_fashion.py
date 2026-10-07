import os
import urllib.request

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,video/*,*/*;q=0.8',
    'Accept-Language': 'en-US,en;q=0.9',
}

urls = [
    ('fashion_1.mp4', 'https://assets.mixkit.co/videos/preview/mixkit-model-walking-on-a-runway-41225-large.mp4'),
    ('fashion_2.mp4', 'https://assets.mixkit.co/videos/preview/mixkit-fashion-model-in-a-photoshoot-wearing-a-white-dress-41235-large.mp4'),
    ('fashion_3.mp4', 'https://assets.mixkit.co/videos/preview/mixkit-woman-posing-for-a-fashion-photoshoot-41231-large.mp4'),
    ('fashion_4.mp4', 'https://assets.mixkit.co/videos/preview/mixkit-young-stylish-model-posing-in-a-fashion-studio-41233-large.mp4'),
    ('fashion_5.mp4', 'https://assets.mixkit.co/videos/preview/mixkit-fashion-model-posing-in-a-studio-41232-large.mp4'),
    ('fashion_6.mp4', 'https://assets.mixkit.co/videos/preview/mixkit-stylish-model-posing-in-a-leather-jacket-40344-large.mp4'),
    ('fashion_7.mp4', 'https://assets.mixkit.co/videos/preview/mixkit-fashion-model-in-a-black-dress-40348-large.mp4'),
    ('fashion_8.mp4', 'https://assets.mixkit.co/videos/preview/mixkit-model-posing-in-neon-lights-40346-large.mp4'),
]

out_dir = r'c:\xamp\htdocs\noir\public\uploads\videos'
os.makedirs(out_dir, exist_ok=True)

for name, url in urls:
    dest = os.path.join(out_dir, name)
    try:
        req = urllib.request.Request(url, headers=headers)
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = resp.read()
            if len(data) > 10000:
                with open(dest, 'wb') as f:
                    f.write(data)
                print(f"DOWNLOADED {name}: {len(data)} bytes")
            else:
                print(f"Too small for {name}: {len(data)} bytes")
    except Exception as e:
        print(f"Error {name}: {e}")
