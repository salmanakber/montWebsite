jQuery(document).ready(($) => {
  // Cache DOM elements
  const $container = $("#article-navigator-container")
  const $postsList = $("#posts-list")
  const $articleDisplay = $("#article-display")
  const $categoryBtns = $(".category-btn")
  const $loadingSpinner = $("#loading-spinner")

  // Track current state
  let currentCategory = "all"
  let currentPostId = null
  let isLoading = false

  // Initialize
  init()

  function init() {
    bindEvents()
    setInitialState()
  }

  function bindEvents() {
    // Category button clicks
    $categoryBtns.on("click", handleCategoryClick)

    // Post item clicks (delegated event for dynamic content)
    $postsList.on("click", ".post-item", handlePostClick)

    // Handle keyboard navigation
    $(document).on("keydown", handleKeyboardNavigation)
  }

  function setInitialState() {
    // Set first post as current if exists
    const $firstPost = $postsList.find(".post-item:first")
    if ($firstPost.length) {
      currentPostId = $firstPost.data("post-id")
      $firstPost.addClass("active")
    }
  }

  function handleCategoryClick(e) {
    e.preventDefault()

    if (isLoading) return

    const $btn = $(this)
    const categoryId = $btn.data("category")

    if (categoryId === currentCategory) return

    // Update UI state
    $categoryBtns.removeClass("active")
    $btn.addClass("active")
    currentCategory = categoryId

    // Load posts for category
    loadPostsByCategory(categoryId)
  }

  function handlePostClick(e) {
    e.preventDefault()

    if (isLoading) return

    const $postItem = $(this)
    const postId = $postItem.data("post-id")

    if (postId === currentPostId) return

    // Update UI state
    $postsList.find(".post-item").removeClass("active")
    $postItem.addClass("active")
    currentPostId = postId

    // Load single post
    loadSinglePost(postId)
  }

  function handleKeyboardNavigation(e) {
    if (!$container.is(":visible")) return

    const $activePost = $postsList.find(".post-item.active")
    let $targetPost = null

    switch (e.keyCode) {
      case 38: // Up arrow
        e.preventDefault()
        $targetPost = $activePost.prev(".post-item")
        break
      case 40: // Down arrow
        e.preventDefault()
        $targetPost = $activePost.next(".post-item")
        break
    }

    if ($targetPost && $targetPost.length) {
      $targetPost.trigger("click")
      scrollToPost($targetPost)
    }
  }

  function loadPostsByCategory(categoryId) {
    if (isLoading) return

    showLoading()

    const data = {
      action: "load_posts_by_category",
      category_id: categoryId,
      posts_per_page: 10,
      nonce: article_navigator_ajax.nonce,
    }

    $.post(article_navigator_ajax.ajax_url, data)
      .done((response) => {
        if (response.success) {
          // Update posts list
          $postsList.html(response.data.posts_html)

          // Update article display with first post
          if (response.data.first_post_html) {
            $articleDisplay.html(response.data.first_post_html)
            currentPostId = response.data.first_post_id

            // Set first post as active
            setTimeout(() => {
              $postsList.find(".post-item:first").addClass("active")
            }, 100)
          } else {
            $articleDisplay.html(
              '<div class="no-posts"><h3>No articles found</h3><p>No articles found in this category.</p></div>',
            )
            currentPostId = null
          }

          // Scroll to top of posts list
          scrollToTop()
        } else {
          showError("Failed to load posts. Please try again.")
        }
      })
      .fail(() => {
        showError("Network error. Please check your connection and try again.")
      })
      .always(() => {
        hideLoading()
      })
  }

  function loadSinglePost(postId) {
    if (isLoading) return

    showLoading()

    const data = {
      action: "load_single_post",
      post_id: postId,
      nonce: article_navigator_ajax.nonce,
    }

    $.post(article_navigator_ajax.ajax_url, data)
      .done((response) => {
        if (response.success) {
          $articleDisplay.html(response.data.post_html)

          // Smooth scroll to article
          $("html, body").animate(
            {
              scrollTop: $articleDisplay.offset().top - 20,
            },
            500,
          )
        } else {
          showError("Failed to load article. Please try again.")
        }
      })
      .fail(() => {
        showError("Network error. Please check your connection and try again.")
      })
      .always(() => {
        hideLoading()
      })
  }

  function showLoading() {
    isLoading = true
    $loadingSpinner.fadeIn(200)
    $container.addClass("loading")
  }

  function hideLoading() {
    isLoading = false
    $loadingSpinner.fadeOut(200)
    $container.removeClass("loading")
  }

  function showError(message) {
    $articleDisplay.html(`
            <div class="error-message" style="text-align: center; padding: 40px; color: #e53e3e;">
                <h3>Error</h3>
                <p>${message}</p>
                <button onclick="location.reload()" style="margin-top: 15px; padding: 10px 20px; background: #3182ce; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Reload Page
                </button>
            </div>
        `)
  }

  function scrollToPost($post) {
    const $sidebar = $(".sidebar-sticky")
    const sidebarScrollTop = $sidebar.scrollTop()
    const postTop = $post.position().top
    const sidebarHeight = $sidebar.height()
    const postHeight = $post.outerHeight()

    if (postTop < 0 || postTop + postHeight > sidebarHeight) {
      $sidebar.animate(
        {
          scrollTop: sidebarScrollTop + postTop - sidebarHeight / 2 + postHeight / 2,
        },
        300,
      )
    }
  }

  function scrollToTop() {
    $("html, body").animate(
      {
        scrollTop: $container.offset().top - 20,
      },
      500,
    )
  }

  // Utility function for debouncing
  function debounce(func, wait) {
    let timeout
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout)
        func(...args)
      }
      clearTimeout(timeout)
      timeout = setTimeout(later, wait)
    }
  }

  // Handle window resize
  $(window).on(
    "resize",
    debounce(() => {
      // Recalculate sticky positioning if needed
      if ($(window).width() <= 768) {
        $(".sidebar-sticky").css("position", "static")
      } else {
        $(".sidebar-sticky").css("position", "sticky")
      }
    }, 250),
  )
})
