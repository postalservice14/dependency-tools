<?php

namespace Yohang;

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Simple static class that installs non-composer dependencies
 *
 * @author Yohan Giarelli <yohan@frequence-web.fr>
 */
class DependencyTools
{
    /**
     * @param $event
     * @throws \RuntimeException
     */
    public static function installDeps($event)
    {
        $options = static::getOptions($event);
        if (false !== $options['npm']) {
            echo "Installing NPM dependencies\n";
            static::execCommand(
                $options['npm'],
                'npm',
                array('install'),
                'An error occurring when installing NPM dependencies'
            );
        }
        if (false !== $options['bower']) {
            echo "Installing Bower dependencies\n";
            static::execCommand(
                $options['bower'],
                'bower',
                array('install'),
                'An error occurring when installing Bower dependencies'
            );
        }
    }

    /**
     * @param $event
     *
     * @return array
     */
    protected static function getOptions($event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        return array_merge(
            array(
                'npm'   => false,
                'bower' => false
            ),
            isset($extra['dependency-tools']) ? $extra['dependency-tools'] : array()
        );
    }

    /**
     * @param array  $options
     * @param string $cmd
     * @param array  $args
     * @param string $ifError
     *
     * @throws \RuntimeException
     */
    protected static function execCommand($options, $cmd, array $args, $ifError)
    {
        if (is_array($options) && isset($options['path'])) {
            $cmd = $options['path'];
        } else {
            $executableFinder = new ExecutableFinder;
            $cmd = $executableFinder->find($cmd);
        }

        $out = '';
        $process = ProcessBuilder::create(array_merge(array($cmd), $args))->getProcess();
        if (isset($options['timeout'])) {
            $process->setTimeout($options['timeout']);
        }
        $process->run(function($type, $buffer) use (&$out) { $out .= $buffer; });

        if (!$process->isSuccessful()) {
            echo 'CMD: ' . $cmd;
            passthru($cmd);
            echo 'EXEC';
            exec($cmd);
            $stackTrace = var_export(xdebug_get_function_stack(), true);
            throw new \RuntimeException($ifError."\n\n".$out."\n\n".$cmd."\n\n".var_export($args, true)."\n\n".$stackTrace);
        }
    }
}
