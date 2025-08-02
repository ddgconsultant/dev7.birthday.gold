# Blog Post API Documentation

## Endpoint Details
- **URL**: `https://dev7.birthday.gold/api/blog-post.php`
- **Method**: `POST`
- **Content-Type**: `application/json`
- **API Key**: `bg-blog-d6215fdcd34a5372eb272dbef1d392650aef8ac4229660e743bcccccab996d41`

## AI Prompt Template

To create a blog post via the API, use the following prompt template:

```
Create a blog post for Birthday Gold about [TOPIC]. Post it to the blog using the following API:

POST https://dev7.birthday.gold/api/blog-post.php
Headers:
- Content-Type: application/json
- X-API-Key: bg-blog-d6215fdcd34a5372eb272dbef1d392650aef8ac4229660e743bcccccab996d41

The blog post should include:
1. An engaging title
2. Well-structured HTML content with proper headings, paragraphs, and lists
3. A compelling description (150-200 characters)
4. Relevant tags
5. Set as 'active' status to publish immediately (or 'draft' to review first)

Format the request body as JSON with these fields:
- title: The blog post title
- content: Full HTML content (use <h2> for main sections, <p> for paragraphs)
- description: Brief summary for previews
- tags: Comma-separated relevant tags
- status: 'active' or 'draft'
- featured: true/false (for homepage feature)
- read_time: Estimated minutes to read
- grouping: Category like 'tips', 'news', 'guides', etc.
```

## Example Request

```json
{
  "title": "10 Creative Birthday Reward Ideas for Your Business",
  "content": "<h2>Introduction</h2><p>Birthday rewards are a powerful way to connect with customers...</p><h2>1. Exclusive Birthday Discounts</h2><p>Offering percentage-based discounts...</p>",
  "description": "Discover creative birthday reward ideas that will delight your customers and boost loyalty for your business.",
  "tags": "birthday rewards, customer loyalty, marketing tips, business growth",
  "status": "active",
  "featured": true,
  "read_time": 7,
  "grouping": "guides"
}
```

## Response Format

### Success Response (201 Created):
```json
{
  "success": true,
  "message": "Blog post created successfully",
  "data": {
    "id": 123,
    "slug": "10-creative-birthday-reward-ideas-for-your-business",
    "url": "https://dev7.birthday.gold/blog/10-creative-birthday-reward-ideas-for-your-business",
    "status": "active"
  }
}
```

### Error Response:
```json
{
  "success": false,
  "message": "Missing required field: content"
}
```

## Content Guidelines for AI

When creating blog content:

1. **Title**: 
   - 40-60 characters
   - Include keywords
   - Make it compelling

2. **Content Structure**:
   - Start with an engaging introduction
   - Use <h2> tags for main sections
   - Include <ul> or <ol> for lists
   - Add <strong> for emphasis
   - Keep paragraphs concise (3-4 sentences)
   - Include a conclusion with call-to-action

3. **SEO Best Practices**:
   - Include target keywords naturally
   - Use semantic HTML
   - Add internal links where relevant
   - Include alt text for any images

4. **Topics to Cover**:
   - Birthday marketing strategies
   - Customer retention tips
   - Business growth through birthday programs
   - Success stories and case studies
   - Industry trends and insights

## Testing with cURL

```bash
curl -X POST https://dev7.birthday.gold/api/blog-post.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: bg-blog-d6215fdcd34a5372eb272dbef1d392650aef8ac4229660e743bcccccab996d41" \
  -d '{
    "title": "Test Blog Post",
    "content": "<p>This is a test blog post.</p>",
    "description": "A test blog post for the API",
    "status": "draft"
  }'
```