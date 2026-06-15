<?php
/**
 * 发布文章页面模板
 *
 * @package custom
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// 初始化上下文
\Widget\Options::alloc()->to($options);
\Widget\User::alloc()->to($user);

// 检查用户是否登录
$isLoggedIn = $user->hasLogin();

// 包含头部文件
$this->need('header.php');
?>

<?php if ($isLoggedIn): ?>
<script>
function editPageManager() {
    return {
        postContent: '',
        mediaFiles: [],
        position: '',
        positionUrl: '',
        visibility: 'public',
        isAdvertise: false,
        isTop: false,
        showLocationPicker: false,
        showVisibilityPicker: false,
        submitStatus: '',
        tags: [],
        tagInput: '',
        externalMediaUrl: '',
        externalMediaType: 'image',
        showExternalMediaInput: false,
        showLinkCardInput: false,
        linkCard: {
            icon: '',
            title: '',
            description: '',
            url: ''
        },

        get visibilityText() {
            const texts = {
                'public': '公开',
                'private': '私密'
            };
            return texts[this.visibility] || '公开';
        },

        get hasLinkCard() {
            return Object.values(this.linkCard).some(value => value.trim() !== '');
        },

        get linkCardDescriptionPreview() {
            return this.linkCard.description.trim() || this.getDomainFromUrl(this.linkCard.url.trim());
        },

        get linkCardPreviewIcon() {
            return this.linkCard.icon.trim();
        },

        autoResize(event) {
            const textarea = event.target;
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        },

        getDomainFromUrl(url) {
            if (!url) return '';
            try {
                return new URL(url).hostname.replace(/^www\./, '');
            } catch (error) {
                return url;
            }
        },

        isAllowedCardUrl(url) {
            return !url || url.startsWith('http://') || url.startsWith('https://');
        },

        clearLinkCard() {
            this.linkCard = {
                icon: '',
                title: '',
                description: '',
                url: ''
            };
            this.showLinkCardInput = false;
        },

        // ---- 标签管理 ----
        addTag() {
            const tag = this.tagInput.trim().replace(/,$/, '');
            if (!tag || tag.length > 20) return;
            if (this.tags.includes(tag)) {
                this.tagInput = '';
                return;
            }
            if (this.tags.length >= 10) {
                alert('最多添加10个标签');
                return;
            }
            this.tags.push(tag);
            this.tagInput = '';
        },

        removeTag(index) {
            this.tags.splice(index, 1);
        },

        handleTagKeydown(event) {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                this.addTag();
            }
        },

        // ---- 外链媒体 ----
        addExternalMedia() {
            const url = this.externalMediaUrl.trim();
            if (!url) return;

            if (!url.startsWith('http://') && !url.startsWith('https://')) {
                alert('请输入以 http:// 或 https:// 开头的链接');
                return;
            }

            const isVideo = this.externalMediaType === 'video';
            const urlType = isVideo ? 'video/mp4' : 'image/jpeg';

            const currentHasVideo = this.mediaFiles.some(m => m.type.startsWith('video/'));
            const currentHasImage = this.mediaFiles.some(m => m.type.startsWith('image/'));

            if (isVideo) {
                if (currentHasVideo) {
                    alert('已上传视频,不能再添加其他文件');
                    return;
                }
                if (currentHasImage) {
                    alert('已上传图片,不能再上传视频');
                    return;
                }
            } else {
                if (currentHasVideo) {
                    alert('已上传视频,不能再添加其他文件');
                    return;
                }
                if (this.mediaFiles.length >= 9) {
                    alert('最多只能上传9张图片');
                    return;
                }
            }

            this.mediaFiles.push({
                url: url,
                type: urlType,
                preview: url,
                source: 'url'
            });

            this.externalMediaUrl = '';
            this.showExternalMediaInput = false;
        },

        // ---- 媒体选择 ----
        handleMediaSelect(event) {
            const files = Array.from(event.target.files);

            const hasVideo = files.some(f => f.type.startsWith('video/'));
            const hasImage = files.some(f => f.type.startsWith('image/'));
            const currentHasVideo = this.mediaFiles.some(f => f.type.startsWith('video/'));
            const currentHasImage = this.mediaFiles.some(f => f.type.startsWith('image/'));

            if (currentHasVideo) {
                alert('已上传视频,不能再添加其他文件');
                event.target.value = '';
                return;
            }

            if (currentHasImage && hasVideo) {
                alert('已上传图片,不能再上传视频');
                event.target.value = '';
                return;
            }

            if (hasVideo) {
                const videoFiles = files.filter(f => f.type.startsWith('video/'));
                if (videoFiles.length > 1) {
                    alert('只能上传1个视频');
                    event.target.value = '';
                    return;
                }
                if (hasImage) {
                    alert('上传视频时不能同时上传图片');
                    event.target.value = '';
                    return;
                }
                const videoFile = videoFiles[0];
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.mediaFiles.push({
                        file: videoFile,
                        type: videoFile.type,
                        preview: e.target.result,
                        source: 'file'
                    });
                };
                reader.readAsDataURL(videoFile);
                event.target.value = '';
                return;
            }

            const remainingSlots = 9 - this.mediaFiles.length;

            if (remainingSlots <= 0) {
                alert('最多只能上传9张图片');
                event.target.value = '';
                return;
            }

            const filesToAdd = files.slice(0, remainingSlots);

            if (files.length > remainingSlots) {
                alert(`最多只能上传9张图片，已自动选择前${remainingSlots}张`);
            }

            filesToAdd.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.mediaFiles.push({
                        file: file,
                        type: file.type,
                        preview: e.target.result,
                        source: 'file'
                    });
                };
                reader.readAsDataURL(file);
            });

            event.target.value = '';
        },

        removeMedia(index) {
            this.mediaFiles.splice(index, 1);
        },

        async submitPost() {
            if (!this.postContent.trim() && this.mediaFiles.length === 0 && !this.hasLinkCard) {
                alert('请输入内容或选择图片/视频');
                return;
            }

            if (this.hasLinkCard && !this.linkCard.title.trim()) {
                alert('请填写链接卡片标题');
                return;
            }

            if (!this.isAllowedCardUrl(this.linkCard.url.trim())) {
                alert('链接地址请使用 http:// 或 https:// 开头');
                return;
            }

            if (!this.isAllowedCardUrl(this.linkCard.icon.trim())) {
                alert('图标地址请使用 http:// 或 https:// 开头');
                return;
            }

            this.submitStatus = '发布中...';

            try {
                // 构建最终内容：将外链媒体以 HTML 标签嵌入
                let finalContent = this.postContent;

                this.mediaFiles.forEach((media) => {
                    if (media.source === 'url') {
                        if (media.type.startsWith('video/')) {
                            finalContent += `\n\n<video src="${media.url}" controls></video>`;
                        } else {
                            finalContent += `\n\n<img src="${media.url}" alt="">`;
                        }
                    }
                });

                const formData = new FormData();
                formData.append('content', finalContent);
                formData.append('position', this.position);
                formData.append('positionUrl', this.positionUrl);
                formData.append('visibility', this.visibility);
                formData.append('isAdvertise', this.isAdvertise ? '1' : '0');
                formData.append('isTop', this.isTop ? '1' : '0');
                formData.append('tags', JSON.stringify(this.tags));
                if (this.hasLinkCard) {
                    formData.append('linkCard', JSON.stringify({
                        icon: this.linkCard.icon.trim(),
                        title: this.linkCard.title.trim(),
                        description: this.linkCard.description.trim(),
                        url: this.linkCard.url.trim(),
                        link: '#link-card'
                    }));
                }

                // 文件上传（非外链）仍以 media_X 发送
                let fileIndex = 0;
                this.mediaFiles.forEach((media) => {
                    if (media.source !== 'url') {
                        formData.append(`media_${fileIndex}`, media.file);
                        fileIndex++;
                    }
                });
                formData.append('media_file_count', fileIndex);

                // 提交到插件文章发布处理器
                const response = await fetch('<?php echo $this->options->index; ?>/action/icefox?do=createPost', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    this.submitStatus = '发布成功！';
                    setTimeout(() => {
                        window.location.href = result.redirect || '/';
                    }, 1000);
                } else {
                    this.submitStatus = '';
                    alert(result.message || '发布失败，请稍后重试');
                }
            } catch (error) {
                this.submitStatus = '';
                alert('网络错误，请稍后重试');
            }
        }
    }
}
</script>
<?php endif; ?>

<style>
/* ---- 标签 ---- */
.edit-tags-editor {
    border-bottom: 1px solid var(--border-color, #e5e5e5);
    padding: 8px 16px 4px;
}
.tags-input-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    min-height: 36px;
    padding: 4px 8px;
    border: 1px solid var(--border-color, #ddd);
    border-radius: 6px;
    cursor: text;
    transition: border-color .2s;
    background: var(--input-bg, #fafafa);
}
.tags-input-row.focused {
    border-color: #467b96;
}
.tags-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}
.tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    font-size: 12px;
    color: #fff;
    background: #467b96;
    border-radius: 4px;
    line-height: 1.6;
}
.tag-chip-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    color: rgba(255,255,255,.8);
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
.tag-chip-remove:hover { color: #fff; }
.tags-input-wrapper {
    flex: 1;
    min-width: 80px;
}
.tags-input {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    font-size: 13px;
    color: inherit;
    padding: 2px 0;
    font-family: inherit;
}
.tags-input::placeholder { font-size: 12px; }
.tags-hint {
    font-size: 11px;
    color: var(--muted-color, #999);
    padding: 2px 8px 0;
}

/* ---- 外链媒体 ---- */
.edit-external-media-bar {
    padding: 8px 0;
}
.external-media-toggle {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: none;
    border: 1px dashed var(--border-color, #ccc);
    border-radius: 4px;
    padding: 4px 10px;
    font-size: 12px;
    color: var(--muted-color, #888);
    cursor: pointer;
    transition: all .2s;
}
.external-media-toggle:hover,
.external-media-toggle.active {
    border-color: #467b96;
    color: #467b96;
}
.external-media-input-area {
    margin-top: 8px;
    padding: 8px;
    background: var(--input-bg, #f5f5f5);
    border-radius: 6px;
}
.external-type-switch {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
}
.external-type-label {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    font-size: 12px;
    border-radius: 4px;
    cursor: pointer;
    color: var(--muted-color, #888);
    background: var(--bg-color, #eee);
    transition: all .2s;
    user-select: none;
}
.external-type-label.active {
    color: #fff;
    background: #467b96;
}
.external-input-row {
    display: flex;
    gap: 6px;
}
.external-url-input {
    flex: 1;
    padding: 6px 10px;
    font-size: 13px;
    border: 1px solid var(--border-color, #ddd);
    border-radius: 4px;
    outline: none;
    background: var(--card-bg, #fff);
    color: inherit;
    font-family: inherit;
}
.external-url-input:focus {
    border-color: #467b96;
}
.external-add-btn {
    padding: 6px 14px;
    font-size: 13px;
    border: none;
    border-radius: 4px;
    background: #467b96;
    color: #fff;
    cursor: pointer;
    white-space: nowrap;
    transition: background .2s;
}
.external-add-btn:hover { background: #3a6579; }
.external-indicator {
    position: absolute;
    top: 4px;
    right: 24px;
    background: rgba(0,0,0,.55);
    color: #fff;
    border-radius: 3px;
    padding: 2px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.media-preview-item.is-external img,
.media-preview-item.is-external video {
    object-fit: cover;
}

/* ---- 链接卡片 ---- */
.edit-link-card-section {
    padding: 8px 0 12px;
}
.link-card-toggle {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: none;
    border: 1px dashed var(--border-color, #ccc);
    border-radius: 4px;
    padding: 4px 10px;
    font-size: 12px;
    color: var(--muted-color, #888);
    cursor: pointer;
    transition: all .2s;
}
.link-card-toggle:hover,
.link-card-toggle.active {
    border-color: #467b96;
    color: #467b96;
}
.link-card-input-area {
    margin-top: 8px;
    padding: 8px;
    background: var(--input-bg, #f5f5f5);
    border-radius: 6px;
}
.link-card-input-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.link-card-input {
    width: 100%;
    padding: 6px 10px;
    font-size: 13px;
    border: 1px solid var(--border-color, #ddd);
    border-radius: 4px;
    outline: none;
    background: var(--card-bg, #fff);
    color: inherit;
    font-family: inherit;
    box-sizing: border-box;
}
.link-card-input:focus {
    border-color: #467b96;
}
.link-card-input.full {
    grid-column: 1 / -1;
}
.link-card-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 8px;
}
.link-card-clear-btn {
    padding: 5px 12px;
    font-size: 12px;
    border: none;
    border-radius: 4px;
    background: #d9534f;
    color: #fff;
    cursor: pointer;
}
.link-card-preview {
    width: min(480px, 100%);
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: var(--text-color, #333);
    background: var(--primary-background, #f7f7f7);
    border-radius: 8px;
    text-decoration: none;
    box-sizing: border-box;
}
.link-card-preview-info {
    min-width: 0;
    flex: 1;
}
.link-card-preview-title {
    font-size: 16px;
    font-weight: 700;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.link-card-preview-desc {
    margin-top: 4px;
    color: var(--text-sub-color, #777);
    font-size: 13px;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.link-card-preview-icon,
.link-card-preview-placeholder {
    width: 56px;
    height: 56px;
    flex: 0 0 56px;
    border-radius: 8px;
}
.link-card-preview-icon {
    object-fit: cover;
}
.link-card-preview-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-sub-color, #888);
    background: var(--body-background, #eee);
}
@media (max-width: 520px) {
    .link-card-input-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main>
    <!-- 发布页面顶部栏 -->
    <section class="edit-top-bar">
        <div class="edit-top-left">
            <a href="javascript:history.back()" class="edit-back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
        </div>
        <div class="edit-top-right">
            <button type="button" class="edit-publish-btn" id="publishBtn" <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>
                发表
            </button>
        </div>
    </section>

    <section class="edit-container">
        <?php if (!$isLoggedIn): ?>
            <!-- 未登录提示 -->
            <div class="edit-login-required" x-data>
                <div class="login-required-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="48" height="48">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <h3>请先登录</h3>
                <p>登录后即可发布内容</p>
                <button type="button" class="login-required-btn"
                        @click="$nextTick(() => { document.querySelector('.login-modal')._x_dataStack[0].loginModalShow = true })">
                    立即登录
                </button>
            </div>
        <?php else: ?>
            <div x-data="editPageManager()">
            <!-- 发布表单 -->
            <form id="editForm" @submit.prevent="submitPost">
                <!-- 文章内容输入区 -->
                <div class="edit-content-area">
                    <textarea
                        name="content"
                        id="postContent"
                        placeholder="这一刻的想法..."
                        x-model="postContent"
                        @input="autoResize($event)"
                        rows="4"></textarea>
                </div>

                <!-- 媒体预览区 - 微信朋友圈九宫格样式 -->
                <div class="edit-media-section">
                    <div class="edit-media-preview" :class="'media-count-' + mediaFiles.length" x-show="mediaFiles.length > 0">
                        <template x-for="(file, index) in mediaFiles" :key="index">
                            <div class="media-preview-item" :class="{'is-video': file.type.startsWith('video/'), 'is-external': file.source === 'url'}">
                                <template x-if="file.type.startsWith('image/')">
                                    <img :src="file.preview" alt="预览图片" referrerpolicy="no-referrer">
                                </template>
                                <template x-if="file.type.startsWith('video/')">
                                    <video :src="file.preview" muted referrerpolicy="no-referrer"></video>
                                </template>
                                <button type="button" class="media-remove-btn" @click="removeMedia(index)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <div class="video-indicator" x-show="file.type.startsWith('video/')">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" width="20" height="20">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                                <div class="external-indicator" x-show="file.source === 'url'" title="外链媒体">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="12" height="12">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </div>
                            </div>
                        </template>
                        <!-- 添加更多按钮 -->
                        <div class="media-add-btn" @click="$refs.mediaInput.click()" x-show="mediaFiles.length > 0 && mediaFiles.length < 9 && !mediaFiles.some(m => m.type.startsWith('video/'))">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="28" height="28">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                    </div>

                    <!-- 添加外链按钮 -->
                    <div class="edit-external-media-bar" x-show="mediaFiles.length === 0 || (!mediaFiles.some(m => m.type.startsWith('video/')) && mediaFiles.length < 9)">
                        <button type="button" class="external-media-toggle" @click="showExternalMediaInput = !showExternalMediaInput" :class="{'active': showExternalMediaInput}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            <span x-text="showExternalMediaInput ? '收起外链' : '外链添加'"></span>
                        </button>
                        <div class="external-media-input-area" x-show="showExternalMediaInput" x-transition>
                            <div class="external-type-switch">
                                <label class="external-type-label" :class="{'active': externalMediaType === 'image'}" @click="externalMediaType = 'image'">
                                    <input type="radio" name="external_type" value="image" x-model="externalMediaType" hidden>图片
                                </label>
                                <label class="external-type-label" :class="{'active': externalMediaType === 'video'}" @click="externalMediaType = 'video'">
                                    <input type="radio" name="external_type" value="video" x-model="externalMediaType" hidden>视频
                                </label>
                            </div>
                            <div class="external-input-row">
                                <input type="url" class="external-url-input" placeholder="粘贴图片/视频链接" x-model="externalMediaUrl" @keydown.enter.prevent="addExternalMedia()">
                                <button type="button" class="external-add-btn" @click="addExternalMedia()">添加</button>
                            </div>
                        </div>
                    </div>

                    <!-- 空状态添加按钮 -->
                    <div class="media-empty-add" @click="$refs.mediaInput.click()" x-show="mediaFiles.length === 0 && !showExternalMediaInput">
                        <div class="media-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="32" height="32">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <span class="media-empty-text">图片/视频</span>
                    </div>
                </div>
                <input type="file"
                       x-ref="mediaInput"
                       accept="image/*,video/*"
                       multiple
                       @change="handleMediaSelect($event)"
                       style="display: none;">

                <div class="edit-link-card-section">
                    <button type="button" class="link-card-toggle" @click="showLinkCardInput = !showLinkCardInput" :class="{'active': showLinkCardInput || hasLinkCard}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                        </svg>
                        <span x-text="showLinkCardInput ? '收起链接卡片' : (hasLinkCard ? '编辑链接卡片' : '链接卡片')"></span>
                    </button>
                    <div class="link-card-input-area" x-show="showLinkCardInput" x-transition>
                        <div class="link-card-input-grid">
                            <input type="text" class="link-card-input" placeholder="标题" x-model="linkCard.title" maxlength="80">
                            <input type="url" class="link-card-input" placeholder="图标 URL" x-model="linkCard.icon">
                            <input type="text" class="link-card-input full" placeholder="描述，不填则显示域名" x-model="linkCard.description" maxlength="160">
                            <input type="url" class="link-card-input full" placeholder="链接 URL，点击卡片在新标签页打开" x-model="linkCard.url">
                        </div>
                        <div class="link-card-actions" x-show="hasLinkCard">
                            <button type="button" class="link-card-clear-btn" @click="clearLinkCard()">清除卡片</button>
                        </div>
                    </div>
                    <a class="link-card-preview" x-show="hasLinkCard && linkCard.title.trim()" :href="linkCard.url || '#link-card'" target="_blank" rel="noopener noreferrer" @click.prevent>
                        <div class="link-card-preview-info">
                            <div class="link-card-preview-title" x-text="linkCard.title"></div>
                            <div class="link-card-preview-desc" x-text="linkCardDescriptionPreview || '链接卡片'"></div>
                        </div>
                        <template x-if="linkCardPreviewIcon">
                            <img class="link-card-preview-icon" :src="linkCardPreviewIcon" alt="" referrerpolicy="no-referrer">
                        </template>
                        <template x-if="!linkCardPreviewIcon">
                            <div class="link-card-preview-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                </svg>
                            </div>
                        </template>
                    </a>
                </div>

                <!-- 功能选项区 -->
                <div class="edit-options">
                    <!-- 所在位置 -->
                    <div class="edit-option-item" @click="showLocationPicker = !showLocationPicker">
                        <div class="option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                        </div>
                        <div class="option-content">
                            <span class="option-label">所在位置</span>
                        </div>
                        <div class="option-value" x-show="position">
                            <span x-text="position"></span>
                        </div>
                        <div class="option-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </div>

                    <!-- 位置编辑弹窗 -->
                    <div class="edit-location-picker" x-show="showLocationPicker" x-transition>
                        <div class="location-picker-input">
                            <input type="text"
                                   placeholder="输入位置名称"
                                   x-model="position">
                            <button type="button" class="location-clear-btn" @click="position = ''" x-show="position">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="location-picker-input">
                            <input type="text"
                                   placeholder="输入跳转地址(选填)"
                                   x-model="positionUrl">
                            <button type="button" class="location-clear-btn" @click="positionUrl = ''" x-show="positionUrl">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="location-picker-actions">
                            <button type="button" class="location-done-btn" @click="showLocationPicker = false">完成</button>
                        </div>
                    </div>

                    <!-- 谁可以看 -->
                    <div class="edit-option-item" @click="showVisibilityPicker = !showVisibilityPicker">
                        <div class="option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                        </div>
                        <div class="option-content">
                            <span class="option-label">谁可以看</span>
                        </div>
                        <div class="option-value">
                            <span x-text="visibilityText"></span>
                        </div>
                        <div class="option-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </div>

                    <!-- 可见性选择弹窗 -->
                    <div class="edit-visibility-picker" x-show="showVisibilityPicker" x-transition>
                        <div class="visibility-option"
                             :class="{'active': visibility === 'public'}"
                             @click="visibility = 'public'; showVisibilityPicker = false">
                            <div class="visibility-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 3.03v.568c0 .334.148.65.405.864l1.068.89c.442.369.535 1.01.216 1.49l-.51.766a2.25 2.25 0 0 1-1.161.886l-.143.048a1.107 1.107 0 0 0-.57 1.664c.369.555.169 1.307-.427 1.605L9 13.125l.423 1.059a.956.956 0 0 1-1.652.928l-.679-.906a1.125 1.125 0 0 0-1.906.172L4.5 15.75l-.612.153M12.75 3.031a9 9 0 1 0 6.712 14.374M12.75 3.031a9 9 0 0 1 6.712 14.374m0 0-.177-.529A2.25 2.25 0 0 0 17.128 15H16.5l-.324-.324a1.453 1.453 0 0 0-2.328.377l-.036.073a1.586 1.586 0 0 1-.982.816l-.99.282c-.55.157-.894.702-.8 1.267l.073.438c.08.474.49.821.97.821.846 0 1.598.542 1.865 1.345l.215.643m5.276-3.67a9.012 9.012 0 0 1-5.276 3.67m0 0a9 9 0 0 1-10.275-4.835M15.75 9c0 .896-.393 1.7-1.016 2.25" />
                                </svg>
                            </div>
                            <div class="visibility-text">
                                <span class="visibility-label">公开</span>
                                <span class="visibility-desc">所有人可见</span>
                            </div>
                            <div class="visibility-check" x-show="visibility === 'public'">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                        </div>
                        <div class="visibility-option"
                             :class="{'active': visibility === 'private'}"
                             @click="visibility = 'private'; showVisibilityPicker = false">
                            <div class="visibility-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                            <div class="visibility-text">
                                <span class="visibility-label">私密</span>
                                <span class="visibility-desc">仅自己可见</span>
                            </div>
                            <div class="visibility-check" x-show="visibility === 'private'">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- 是否是广告 -->
                    <div class="edit-option-item">
                        <div class="option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                            </svg>
                        </div>
                        <div class="option-content">
                            <span class="option-label">广告内容</span>
                        </div>
                        <div class="option-switch">
                            <label class="switch">
                                <input type="checkbox" x-model="isAdvertise">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- 是否置顶 -->
                    <div class="edit-option-item">
                        <div class="option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                            </svg>
                        </div>
                        <div class="option-content">
                            <span class="option-label">置顶</span>
                        </div>
                        <div class="option-switch">
                            <label class="switch">
                                <input type="checkbox" x-model="isTop">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- 标签 -->
                    <div class="edit-option-item" @click="showLocationPicker = false; showVisibilityPicker = false">
                        <div class="option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                            </svg>
                        </div>
                        <div class="option-content">
                            <span class="option-label">标签</span>
                        </div>
                        <div class="option-value" x-show="tags.length > 0">
                            <span x-text="tags.length + '个'"></span>
                        </div>
                        <div class="option-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </div>

                    <!-- 标签编辑区 -->
                    <div class="edit-tags-editor" x-data="{ tagOpen: false }" x-init="() => { $watch('tags', () => { if (tags.length > 0) tagOpen = true }) }">
                        <div class="tags-input-row" @click="tagOpen = true" :class="{'focused': tagOpen}">
                            <div class="tags-chips">
                                <template x-for="(tag, i) in tags" :key="i">
                                    <span class="tag-chip">
                                        <span x-text="tag"></span>
                                        <button type="button" class="tag-chip-remove" @click.stop="removeTag(i)">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="12" height="12">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                            </div>
                            <div class="tags-input-wrapper" x-show="tagOpen || tags.length === 0">
                                <input type="text" class="tags-input" placeholder="输入标签，按回车添加" x-model="tagInput" @keydown="handleTagKeydown($event)" @blur="addTag()" maxlength="20">
                            </div>
                        </div>
                        <div class="tags-hint" x-show="tagOpen">
                            <span>最多10个标签，回车添加</span>
                        </div>
                    </div>

                    <!-- 同步到其他平台（占位） -->
                    <!--
                    <div class="edit-option-item">
                        <div class="option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                            </svg>
                        </div>
                        <div class="option-content">
                            <span class="option-label">同步</span>
                        </div>
                        <div class="option-value">
                            <span class="option-placeholder">不同步</span>
                        </div>
                        <div class="option-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </div>
                    -->
                </div>

                <!-- 提交状态提示 -->
                <div class="edit-status" x-show="submitStatus" x-transition>
                    <span x-text="submitStatus"></span>
                </div>
            </form>
            </div>
        <?php endif; ?>
    </section>

    <?php $this->need('components/modals/setting.php'); ?>
    <?php $this->need('components/modals/login.php'); ?>
</main>

<?php if ($isLoggedIn): ?>
<script>
// 绑定发表按钮
document.addEventListener('DOMContentLoaded', function() {
    const publishBtn = document.getElementById('publishBtn');
    const editForm = document.getElementById('editForm');

    if (publishBtn && editForm) {
        publishBtn.addEventListener('click', function() {
            const submitEvent = new Event('submit', { cancelable: true });
            editForm.dispatchEvent(submitEvent);
        });
    }
});
</script>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
