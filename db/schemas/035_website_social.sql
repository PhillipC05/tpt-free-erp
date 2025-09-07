-- TPT Open ERP - Website & Social Media Schema
-- Migration: 035
-- Description: Website and social media management tables

-- Website Pages Table
CREATE TABLE IF NOT EXISTS website_pages (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    slug VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    excerpt VARCHAR(500),
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    meta_keywords TEXT,
    canonical_url TEXT,
    featured_image TEXT,
    template VARCHAR(100) DEFAULT 'default',
    status VARCHAR(50) DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    is_homepage BOOLEAN DEFAULT FALSE,
    is_searchable BOOLEAN DEFAULT TRUE,
    parent_page_id INTEGER REFERENCES website_pages(id),
    menu_order INTEGER DEFAULT 0,
    layout JSONB,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT website_pages_status_check CHECK (status IN ('draft', 'published', 'archived', 'scheduled'))
);

-- Website Posts Table
CREATE TABLE IF NOT EXISTS website_posts (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    slug VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    excerpt VARCHAR(500),
    author_id INTEGER REFERENCES users(id),
    category_id INTEGER,
    tags TEXT[],
    featured_image TEXT,
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    meta_keywords TEXT,
    canonical_url TEXT,
    status VARCHAR(50) DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    is_sticky BOOLEAN DEFAULT FALSE,
    allow_comments BOOLEAN DEFAULT TRUE,
    comment_count INTEGER DEFAULT 0,
    view_count INTEGER DEFAULT 0,
    like_count INTEGER DEFAULT 0,
    share_count INTEGER DEFAULT 0,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT website_posts_status_check CHECK (status IN ('draft', 'published', 'archived', 'scheduled'))
);

-- Website Categories Table
CREATE TABLE IF NOT EXISTS website_categories (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    parent_category_id INTEGER REFERENCES website_categories(id),
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    post_count INTEGER DEFAULT 0,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Website Comments Table
CREATE TABLE IF NOT EXISTS website_comments (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    post_id INTEGER NOT NULL REFERENCES website_posts(id) ON DELETE CASCADE,
    parent_comment_id INTEGER REFERENCES website_comments(id),
    author_name VARCHAR(255),
    author_email VARCHAR(255),
    author_url TEXT,
    author_ip INET,
    content TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    is_approved BOOLEAN DEFAULT FALSE,
    like_count INTEGER DEFAULT 0,
    reply_count INTEGER DEFAULT 0,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT website_comments_status_check CHECK (status IN ('pending', 'approved', 'spam', 'trash'))
);

-- Social Media Accounts Table
CREATE TABLE IF NOT EXISTS social_accounts (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    platform VARCHAR(100) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_id VARCHAR(255),
    account_url TEXT,
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    follower_count INTEGER DEFAULT 0,
    following_count INTEGER DEFAULT 0,
    post_count INTEGER DEFAULT 0,
    last_sync TIMESTAMP NULL,
    sync_frequency VARCHAR(50) DEFAULT 'hourly',
    auto_post BOOLEAN DEFAULT FALSE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT social_accounts_platform_check CHECK (platform IN ('facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok', 'pinterest')),
    CONSTRAINT social_accounts_sync_check CHECK (sync_frequency IN ('realtime', 'hourly', 'daily', 'weekly', 'manual'))
);

-- Social Media Posts Table
CREATE TABLE IF NOT EXISTS social_posts (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    account_id INTEGER NOT NULL REFERENCES social_accounts(id),
    post_type VARCHAR(50) DEFAULT 'text',
    content TEXT,
    media_urls TEXT[],
    link_url TEXT,
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    status VARCHAR(50) DEFAULT 'draft',
    engagement_rate DECIMAL(5,2),
    like_count INTEGER DEFAULT 0,
    share_count INTEGER DEFAULT 0,
    comment_count INTEGER DEFAULT 0,
    view_count INTEGER DEFAULT 0,
    click_count INTEGER DEFAULT 0,
    platform_post_id VARCHAR(255),
    platform_post_url TEXT,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT social_posts_type_check CHECK (post_type IN ('text', 'image', 'video', 'link', 'carousel', 'story')),
    CONSTRAINT social_posts_status_check CHECK (status IN ('draft', 'scheduled', 'published', 'failed'))
);

-- Social Media Analytics Table
CREATE TABLE IF NOT EXISTS social_analytics (
    id SERIAL PRIMARY KEY,
    account_id INTEGER NOT NULL REFERENCES social_accounts(id),
    post_id INTEGER REFERENCES social_posts(id),
    date_recorded DATE NOT NULL,
    metric_type VARCHAR(100) NOT NULL,
    metric_value INTEGER NOT NULL,
    platform_data JSONB,

    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    UNIQUE(account_id, post_id, date_recorded, metric_type)
);

-- Website Media Library Table
CREATE TABLE IF NOT EXISTS website_media (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_url TEXT NOT NULL,
    file_size INTEGER NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    alt_text VARCHAR(255),
    caption TEXT,
    description TEXT,
    dimensions VARCHAR(50),
    uploaded_by INTEGER REFERENCES users(id),
    is_public BOOLEAN DEFAULT TRUE,
    usage_count INTEGER DEFAULT 0,

    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Website Redirects Table
CREATE TABLE IF NOT EXISTS website_redirects (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    old_url TEXT NOT NULL,
    new_url TEXT NOT NULL,
    redirect_type VARCHAR(10) DEFAULT '301',
    is_active BOOLEAN DEFAULT TRUE,
    hit_count INTEGER DEFAULT 0,
    last_hit TIMESTAMP NULL,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT website_redirects_type_check CHECK (redirect_type IN ('301', '302', '307', '308')),
    UNIQUE(old_url)
);

-- Website Forms Table
CREATE TABLE IF NOT EXISTS website_forms (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    form_schema JSONB NOT NULL,
    submit_button_text VARCHAR(100) DEFAULT 'Submit',
    success_message TEXT,
    redirect_url TEXT,
    email_recipients TEXT[],
    is_active BOOLEAN DEFAULT TRUE,
    submission_count INTEGER DEFAULT 0,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Website Form Submissions Table
CREATE TABLE IF NOT EXISTS website_form_submissions (
    id SERIAL PRIMARY KEY,
    form_id INTEGER NOT NULL REFERENCES website_forms(id) ON DELETE CASCADE,
    submission_data JSONB NOT NULL,
    ip_address INET,
    user_agent TEXT,
    referrer_url TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    notes TEXT,

    -- Audit Fields
    submitted_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Website SEO Settings Table
CREATE TABLE IF NOT EXISTS website_seo_settings (
    id SERIAL PRIMARY KEY,
    page_type VARCHAR(100) NOT NULL,
    page_id INTEGER,
    meta_title VARCHAR(255),
    meta_description VARCHAR(500),
    meta_keywords TEXT,
    canonical_url TEXT,
    og_title VARCHAR(255),
    og_description VARCHAR(500),
    og_image TEXT,
    twitter_card VARCHAR(50) DEFAULT 'summary',
    structured_data JSONB,
    noindex BOOLEAN DEFAULT FALSE,
    nofollow BOOLEAN DEFAULT FALSE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT website_seo_twitter_check CHECK (twitter_card IN ('summary', 'summary_large_image', 'app', 'player')),
    UNIQUE(page_type, page_id)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_website_pages_uuid ON website_pages(uuid);
CREATE INDEX IF NOT EXISTS idx_website_pages_slug ON website_pages(slug);
CREATE INDEX IF NOT EXISTS idx_website_pages_status ON website_pages(status);
CREATE INDEX IF NOT EXISTS idx_website_pages_parent ON website_pages(parent_page_id);

CREATE INDEX IF NOT EXISTS idx_website_posts_uuid ON website_posts(uuid);
CREATE INDEX IF NOT EXISTS idx_website_posts_slug ON website_posts(slug);
CREATE INDEX IF NOT EXISTS idx_website_posts_author ON website_posts(author_id);
CREATE INDEX IF NOT EXISTS idx_website_posts_status ON website_posts(status);
CREATE INDEX IF NOT EXISTS idx_website_posts_published ON website_posts(published_at);

CREATE INDEX IF NOT EXISTS idx_website_categories_slug ON website_categories(slug);
CREATE INDEX IF NOT EXISTS idx_website_categories_parent ON website_categories(parent_category_id);

CREATE INDEX IF NOT EXISTS idx_website_comments_post ON website_comments(post_id);
CREATE INDEX IF NOT EXISTS idx_website_comments_status ON website_comments(status);

CREATE INDEX IF NOT EXISTS idx_social_accounts_platform ON social_accounts(platform);
CREATE INDEX IF NOT EXISTS idx_social_accounts_active ON social_accounts(is_active);

CREATE INDEX IF NOT EXISTS idx_social_posts_account ON social_posts(account_id);
CREATE INDEX IF NOT EXISTS idx_social_posts_status ON social_posts(status);
CREATE INDEX IF NOT EXISTS idx_social_posts_scheduled ON social_posts(scheduled_at);
CREATE INDEX IF NOT EXISTS idx_social_posts_published ON social_posts(published_at);

CREATE INDEX IF NOT EXISTS idx_social_analytics_account ON social_analytics(account_id);
CREATE INDEX IF NOT EXISTS idx_social_analytics_post ON social_analytics(post_id);
CREATE INDEX IF NOT EXISTS idx_social_analytics_date ON social_analytics(date_recorded);

CREATE INDEX IF NOT EXISTS idx_website_media_uploaded_by ON website_media(uploaded_by);

CREATE INDEX IF NOT EXISTS idx_website_redirects_active ON website_redirects(is_active);

CREATE INDEX IF NOT EXISTS idx_website_forms_slug ON website_forms(slug);

CREATE INDEX IF NOT EXISTS idx_website_form_submissions_form ON website_form_submissions(form_id);

CREATE INDEX IF NOT EXISTS idx_website_seo_page ON website_seo_settings(page_type, page_id);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_website_pages_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_website_pages_updated_at BEFORE UPDATE ON website_pages
    FOR EACH ROW EXECUTE FUNCTION update_website_pages_updated_at();

CREATE OR REPLACE FUNCTION update_website_posts_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_website_posts_updated_at BEFORE UPDATE ON website_posts
    FOR EACH ROW EXECUTE FUNCTION update_website_posts_updated_at();

CREATE OR REPLACE FUNCTION update_website_categories_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_website_categories_updated_at BEFORE UPDATE ON website_categories
    FOR EACH ROW EXECUTE FUNCTION update_website_categories_updated_at();

CREATE OR REPLACE FUNCTION update_website_comments_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_website_comments_updated_at BEFORE UPDATE ON website_comments
    FOR EACH ROW EXECUTE FUNCTION update_website_comments_updated_at();

CREATE OR REPLACE FUNCTION update_social_accounts_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_social_accounts_updated_at BEFORE UPDATE ON social_accounts
    FOR EACH ROW EXECUTE FUNCTION update_social_accounts_updated_at();

CREATE OR REPLACE FUNCTION update_social_posts_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_social_posts_updated_at BEFORE UPDATE ON social_posts
    FOR EACH ROW EXECUTE FUNCTION update_social_posts_updated_at();

CREATE OR REPLACE FUNCTION update_website_media_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_website_media_updated_at BEFORE UPDATE ON website_media
    FOR EACH ROW EXECUTE FUNCTION update_website_media_updated_at();

CREATE OR REPLACE FUNCTION update_website_redirects_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_website_redirects_updated_at BEFORE UPDATE ON website_redirects
    FOR EACH ROW EXECUTE FUNCTION update_website_redirects_updated_at();

CREATE OR REPLACE FUNCTION update_website_forms_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_website_forms_updated_at BEFORE UPDATE ON website_forms
    FOR EACH ROW EXECUTE FUNCTION update_website_forms_updated_at();

CREATE OR REPLACE FUNCTION update_website_form_submissions_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_website_form_submissions_updated_at BEFORE UPDATE ON website_form_submissions
    FOR EACH ROW EXECUTE FUNCTION update_website_form_submissions_updated_at();

CREATE OR REPLACE FUNCTION update_website_seo_settings_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_website_seo_settings_updated_at BEFORE UPDATE ON website_seo_settings
    FOR EACH ROW EXECUTE FUNCTION update_website_seo_settings_updated_at();

-- Comments
COMMENT ON TABLE website_pages IS 'CMS pages for the website';
COMMENT ON TABLE website_posts IS 'Blog posts and articles';
COMMENT ON TABLE website_categories IS 'Categories for organizing posts';
COMMENT ON TABLE website_comments IS 'Comments on posts';
COMMENT ON TABLE social_accounts IS 'Social media account configurations';
COMMENT ON TABLE social_posts IS 'Social media posts and scheduling';
COMMENT ON TABLE social_analytics IS 'Social media engagement analytics';
COMMENT ON TABLE website_media IS 'Media library for website assets';
COMMENT ON TABLE website_redirects IS 'URL redirects for SEO';
COMMENT ON TABLE website_forms IS 'Custom forms for the website';
COMMENT ON TABLE website_form_submissions IS 'Form submission data';
COMMENT ON TABLE website_seo_settings IS 'SEO settings for pages and posts';
