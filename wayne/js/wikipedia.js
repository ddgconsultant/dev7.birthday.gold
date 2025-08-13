// Wikipedia Enrichment Feature

class WikipediaEnrichment {
    constructor() {
        this.apiKey = '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';
        this.model = 'claude-opus-4-20250514';
        this.init();
    }
    
    init() {
        const form = document.getElementById('wikiForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const input = document.getElementById('wikiInput');
        const resultsDiv = document.getElementById('wikiResults');
        const loadingDiv = resultsDiv.querySelector('.wiki-loading');
        const contentDiv = resultsDiv.querySelector('.wiki-content');
        
        const text = input.value.trim();
        if (!text) return;
        
        // Show loading
        resultsDiv.style.display = 'block';
        loadingDiv.style.display = 'flex';
        contentDiv.innerHTML = '';
        
        try {
            let wikiContent = text;
            
            // Check if it's a URL
            if (text.startsWith('http') && text.includes('wikipedia.org')) {
                // Extract and fetch Wikipedia content
                wikiContent = await this.fetchWikipediaContent(text);
            }
            
            // Send to Claude for analysis
            const enrichedData = await this.analyzeWithClaude(wikiContent);
            
            // Display results
            loadingDiv.style.display = 'none';
            this.displayResults(enrichedData, contentDiv);
            
        } catch (error) {
            console.error('Full error:', error);
            loadingDiv.style.display = 'none';
            
            // Provide more helpful error message
            let errorMessage = error.message;
            if (error.message.includes('API')) {
                errorMessage += '<br><small>Check the browser console for more details.</small>';
            }
            contentDiv.innerHTML = `<div class="wiki-error">Error: ${errorMessage}</div>`;
        }
    }
    
    async fetchWikipediaContent(url) {
        // Extract article title from URL
        const urlParts = url.split('/');
        const title = urlParts[urlParts.length - 1];
        
        // Use Wikipedia API to get content
        const apiUrl = `https://en.wikipedia.org/api/rest_v1/page/summary/${title}`;
        
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) throw new Error('Failed to fetch Wikipedia article');
            
            const data = await response.json();
            return `${data.title}\n\n${data.extract}`;
        } catch (error) {
            // If API fails, return the URL for Claude to work with
            return `Please analyze this Wikipedia article: ${url}`;
        }
    }
    
    async analyzeWithClaude(content) {
        // Truncate content if too long
        const maxLength = 3000;
        const truncatedContent = content.length > maxLength 
            ? content.substring(0, maxLength) + '...' 
            : content;
        
        const prompt = `You are analyzing content about a person, place, or topic. If only a name is provided, use your knowledge to provide information about that subject.
        
Input: ${truncatedContent}

Please provide enrichment data about this subject:
1. Key Facts - 3-5 most important facts
2. Historical Context - Important background or timeline
3. Lesser Known Information - 2-3 interesting facts that are not commonly known
4. Related Topics - 3-4 related subjects worth exploring
5. Recent Developments or Legacy - Current relevance or lasting impact

Format your response with clear sections and bullet points.`;
        
        try {
            const response = await fetch('api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: prompt,
                    apiKey: this.apiKey,
                    provider: 'anthropic',
                    model: this.model,
                    history: []
                })
            });
            
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (!data.success) {
                throw new Error(data.error || 'AI analysis failed');
            }
            
            return data.response;
        } catch (error) {
            console.error('Error calling API:', error);
            throw error;
        }
    }
    
    displayResults(enrichedData, container) {
        // Parse and format the enriched data
        const sections = this.parseEnrichedData(enrichedData);
        
        let html = '<h5>🔍 AI Analysis & Enrichment</h5>';
        
        for (const section of sections) {
            html += `
                <div class="wiki-section">
                    <h5>${section.title}</h5>
                    ${section.content}
                </div>
            `;
        }
        
        container.innerHTML = html;
    }
    
    parseEnrichedData(data) {
        // Split the response into sections
        const sections = [];
        const lines = data.split('\n');
        let currentSection = null;
        let currentContent = [];
        
        for (const line of lines) {
            // Check if this is a section header (contains numbers or starts with specific keywords)
            if (line.match(/^\d+\.|^[A-Z][^:]*:/)) {
                if (currentSection) {
                    sections.push({
                        title: currentSection,
                        content: this.formatContent(currentContent.join('\n'))
                    });
                }
                currentSection = line.replace(/^\d+\.\s*/, '').replace(/:$/, '');
                currentContent = [];
            } else if (line.trim()) {
                currentContent.push(line);
            }
        }
        
        // Add the last section
        if (currentSection) {
            sections.push({
                title: currentSection,
                content: this.formatContent(currentContent.join('\n'))
            });
        }
        
        // If no sections were found, just display the whole content
        if (sections.length === 0) {
            sections.push({
                title: 'Analysis',
                content: this.formatContent(data)
            });
        }
        
        return sections;
    }
    
    formatContent(content) {
        // Convert bullet points to HTML lists
        const lines = content.split('\n');
        let html = '';
        let inList = false;
        
        for (const line of lines) {
            const trimmedLine = line.trim();
            
            if (trimmedLine.startsWith('•') || trimmedLine.startsWith('-') || trimmedLine.startsWith('*')) {
                if (!inList) {
                    html += '<ul>';
                    inList = true;
                }
                html += `<li>${trimmedLine.substring(1).trim()}</li>`;
            } else if (trimmedLine) {
                if (inList) {
                    html += '</ul>';
                    inList = false;
                }
                html += `<p>${trimmedLine}</p>`;
            }
        }
        
        if (inList) {
            html += '</ul>';
        }
        
        return html || '<p>' + content + '</p>';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new WikipediaEnrichment();
});