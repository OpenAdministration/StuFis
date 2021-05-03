<h3>{$code} - {$headline}</h3>
{if isset($telegramIssueLink) || isset($githubIssueLink)}
    <p>
        Bitte melde diesen Fehler per
        {if isset($telegramIssueLink)}
            <a href="{$telegramIssueLink}>"><i class="fa fa-fw fa-telegram"></i>Telegram</a>
        {/if}
        {if isset($telegramIssueLink) && isset($githubIssueLink)}
            oder
        {/if}
        {if isset($githubIssueLink)}
            <a href="{$githubIssueLink}?issuable_template=user_error_report"><i class="fa fa-fw fa-gitlab"></i>Gitlab</a> (Freischaltung evtl. erforderlich)
        {/if}
        damit er in Zukunft beseitigt werden kann.
    </p>
{/if}
<p>
    <strong>{$msg}</strong>
</p>
{if !empty($additional)}
    <pre>{$additional}</pre>
{/if}
{if DEV}
    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="headingOne">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        Show Trace Log
                    </a>
                </h4>
            </div>
            <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                <div class="panel-body">
                    <pre>{strip}{foreach $trace as $item}
                        {$item.file}({$item.line}) - {$item.class}::{$item.function}()
                    {/foreach}{/strip}</pre>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="headingOne">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseZwo" aria-expanded="true" aria-controls="collapseZwo">
                        Debug
                    </a>
                </h4>
            </div>
            <div id="collapseZwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingZwo">
                <div class="panel-body">
                <pre>{$debug}</pre>
                </div>
            </div>
        </div>
    </div>
{/if}


