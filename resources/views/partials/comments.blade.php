<!-- This is an insert so no layout required -->
<div class="comments-section">
    <h3 class="font-bold text-lg">Comments ({{ $comments->count() }})</h3>
    <!-- Display the Comments -->
    @foreach($comments as $comment)
        <div class="comment my-6 text-sm border border-slate-200 rounded-lg p-5 shadow-lg bg-slate-50">
            {{ $comment->created_at->diffForHumans() }} <a class="underline text-lime-700 hover:text-lime-500" href="/profile/{{ $comment->user->name_slug }}">{{ $comment->user->name }}</a> said:
            <p class="my-4">{{ $comment->comment_text }}</p>
        </div>
    @endforeach
    <!-- Comment Form -->
    @can('Member')
    <div class="mt-10">
        <form action="{{ route('comments.store') }}" method="POST">
            @csrf
            <input type="hidden" name="commentable_type" value="{{ get_class($model) }}">
            <input type="hidden" name="commentable_id" value="{{ $model->id }}">
            <textarea name="comment_text" class="w-full border border-slate-200 rounded-lg shadow-lg p-4" rows="2" placeholder="Add your comment"></textarea>
            <button type="submit" class="standard-button">Submit Comment</button>
        </form>
        @endcan
        @guest
            <div class="mt-4">
                <p class="">Want to comment on this page? <a href="/login" class="text-lime-700">Login</a> or <a href="/register" class="text-lime-700">Register</a>.</p>
            </div>
        @endguest
    </div>
</div>