<?php
/**
 * This file is part of the <Cmf> project.
 *
 * @subpackage   Widget
 * @package    Extension
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @since 2012-02-10
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sfynx\CmfBundle\Twig\TokenParser;
 
use Sfynx\CmfBundle\Twig\Node\StyleSheetWidgetNode;

/**
 * StyleSheet Widget Token Parser.
 *
 * @subpackage   Widget
 * @package    Extension
 * @author Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
class StyleSheetWidgetTokenParser extends \Twig_TokenParser
{
    /**
     * @var string $extensionName
     */
    protected $extensionName;
    
    /**
     * Constructor.
     *
     * @param string $extensionName The extension name
     */
    public function __construct($extensionName)
    {
        $this->extensionName = $extensionName;
    }
        
    /**
     * Parses a token and returns a node.
     *
     * @param \Twig_Token $token A \Twig_Token instance
     * @return \Twig_NodeInterface A \Twig_NodeInterface instance
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $value = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new StyleSheetWidgetNode($this->extensionName, $value, $lineno, $this->getTag());
    }

    /**
     * Gets the tag name associated with this token parser.
     * @param string The tag name
     */
    public function getTag()
    {
        return 'initWidget';
    }
}
